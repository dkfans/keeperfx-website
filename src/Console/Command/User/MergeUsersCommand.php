<?php

namespace App\Console\Command\User;

use App\Enum\UserRole;

use App\Entity\User;
use App\Entity\UserIpLog;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopRating;
use App\Entity\UserOAuthToken;
use App\Entity\WorkshopComment;
use App\Entity\UserCookieToken;
use App\Entity\UserNotification;
use App\Entity\WorkshopCommentReport;
use App\Entity\UserPasswordResetToken;
use App\Entity\UserNotificationSetting;

use App\Config\Config;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Xenokore\Utility\Helper\FileHelper;

class MergeUsersCommand extends Command
{
    public function __construct(
        private EntityManager $em
    ){
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("user:merge")
                ->setDescription("Merge 2 user accounts")
                ->addArgument('main_account_username', InputArgument::REQUIRED, 'Main account username')
                ->addArgument('secondary_account_username', InputArgument::REQUIRED, 'Secondary account username');
    }

    protected function execute(Input $input, Output $output)
    {
        // Get arguments
        $main_username  = (string) $input->getArgument('main_account_username');
        $secondary_username = (string) $input->getArgument('secondary_account_username');

        // Get main user
        /** @var ?User $main_user */
        $main_user  = $this->em->getRepository(User::class)->findOneBy(['username' => $main_username]);
        if($main_user === null){
            $output->writeln("[-] Main user with username '{$main_username}' not found");
            return Command::FAILURE;
        }

        // Get secondary user
        /** @var ?User $secondary_user */
        $secondary_user = $this->em->getRepository(User::class)->findOneBy(['username' => $secondary_username]);
        if($secondary_user === null){
            $output->writeln("[-] Secondary user with username '{$secondary_username}' not found");
            return Command::FAILURE;
        }

        // Show info
        $output->writeln("");
        $output->writeln("[>] <info>User 1</info>:");
        $output->writeln('[>]   ID:                  <info>' . $main_user->getId() . '</info>');
        $output->writeln('[>]   Username:            <info>' . $main_user->getUsername() . '</info>');
        $output->writeln('[>]   Email:               <info>' . ($main_user->getEmail() ?? '-') . '</info>');
        $output->writeln('[>]   Registration date:   <info>' . $main_user->getCreatedTimestamp()->format('Y-m-d G:i') . '</info>');
        $output->writeln('[>]   Workshop items:      <info>' . $main_user->getWorkshopItems()->count() . '</info>');
        $output->writeln('[>]   Workshop ratings:    <info>' . $main_user->getWorkshopRatings()->count() . '</info>');
        $output->writeln('[>]   Workshop comments:   <info>' . $main_user->getWorkshopComments()->count() . '</info>');
        $output->writeln("");
        $output->writeln("[>] <info>User 2</info>:");
        $output->writeln('[>]   ID:                  <info>' . $secondary_user->getId() . '</info>');
        $output->writeln('[>]   Username:            <info>' . $secondary_user->getUsername() . '</info>');
        $output->writeln('[>]   Email:               <info>' . ($secondary_user->getEmail() ?? '-') . '</info>');
        $output->writeln('[>]   Registration date:   <info>' . $secondary_user->getCreatedTimestamp()->format('Y-m-d G:i') . '</info>');
        $output->writeln('[>]   Workshop items:      <info>' . $secondary_user->getWorkshopItems()->count() . '</info>');
        $output->writeln('[>]   Workshop ratings:    <info>' . $secondary_user->getWorkshopRatings()->count() . '</info>');
        $output->writeln('[>]   Workshop comments:   <info>' . $secondary_user->getWorkshopComments()->count() . '</info>');
        $output->writeln("");

        // Move email
        if($secondary_user->getEmail() !== null){
            if($main_user->getEmail() === null){
                $main_user->setEmail($secondary_user->getEmail());
                $main_user->setEmailVerified($secondary_user->isEmailVerified());
                $output->writeln("[+] Move <info>{$secondary_user->getEmail()}</info> to <info>{$main_username}</info>");
            } else {
                $output->writeln("[?] <info>{$main_username}</info> already has an email address: <info>{$main_user->getEmail()}</info>");
                $output->writeln("[+] Remove <info>{$secondary_user->getEmail()}</info>");
            }
        }

        // Move possible email verification
        if($secondary_user->getEmailVerification() !== null){
            $secondary_user->getEmailVerification()->setUser($main_user);
            $output->writeln("[+] Move email verification token");
        }

        // Move country
        if($secondary_user->getCountry() !== null && $main_user->getCountry() === null){
            $output->writeln("[+] Update country: <info>{$secondary_user->getCountry()}</info>");
            $main_user->setCountry($secondary_user->getCountry());
        }

        // Move avatar setup
        $avatar_file_to_remove = null;
        if($secondary_user->getAvatar() !== null){
            if($main_user->getAvatar() !== null){
                $output->writeln("[+] Removing avatar");
                $avatar_file_to_remove = $secondary_user->getAvatar();
            } else {
                $output->writeln("[+] Moving avatar: <info>{$secondary_user->getAvatar()}</info>");
                $main_user->setAvatar($secondary_user->getAvatar());
            }
        }

        // Move small avatar setup
        $small_avatar_file_to_remove = null;
        if($secondary_user->getAvatarSmall() !== null){
            if($main_user->getAvatarSmall() !== null){
                $output->writeln("[+] Removing small avatar");
                $small_avatar_file_to_remove = $secondary_user->getAvatarSmall();
            } else {
                $output->writeln("[+] Moving small avatar: <info>{$secondary_user->getAvatarSmall()}</info>");
                $main_user->setAvatarSmall($secondary_user->getAvatarSmall());
            }
        }

        // Set registration date to earliest registered account
        if($secondary_user->getCreatedTimestamp() < $main_user->getCreatedTimestamp()){
            $main_user->setCreatedTimestamp($secondary_user->getCreatedTimestamp());
            $output->writeln("[+] Update registration date to <info>" . $secondary_user->getCreatedTimestamp()->format('Y-m-d G:i') . "</info>");
        }

        // Move bio
        if(!empty($secondary_user->getBio()?->getBio()) && empty($main_user->getBio()?->getBio())){

            // Remove main user bio entity if it exists
            // This will only be true when the bio is an empty string
            if($main_user->getBio()){
                $this->em->remove($main_user->getBio());
            }

            // Move bio over
            $secondary_user->getBio()->setUser($main_user);
            $output->writeln("[+] Update <info>bio</info>");
        }

        // Move OAuth tokens
        $removed_oauth_tokens = [];
        /** @var UserOAuthToken $token */
        foreach($secondary_user->getConnectionTokens() as $token){

            $already_has_token = false;

            // Check if main user already has this OAuth service linked
            /** @var UserOAuthToken $main_token */
            foreach($main_user->getConnectionTokens() as $main_token){
                if($main_token->getProviderType() == $token->getProviderType()){
                    $output->writeln("[+] Main account already has an OAuth token for <info>{$token->getProviderType()->value}</info>");
                    $already_has_token = true;
                    break;
                }
            }

            // Move token to main user
            if($already_has_token == false){
                $output->writeln("[+] Move OAuth <info>{$token->getProviderType()->value}</info> token for uid <info>{$token->getUid()}</info>: <info>{$token->getToken()}</info>");
                $token->setUser($main_user);
            }

            // Remove token because main user already has this service linked
            if($already_has_token == true){
                $output->writeln("[+] Remove OAuth <info>{$token->getProviderType()->value}</info> token for uid <info>{$token->getUid()}</info>: <info>{$token->getToken()}</info>");
                $this->em->remove($token);
                $removed_oauth_tokens[] = $token;
            }
        }

        // Move cookie tokens
        /** @var UserCookieToken $cookie_token */
        foreach($secondary_user->getCookieTokens() as $cookie_token){

            // Check if cookie is linked to OAuth token
            if($cookie_token->getOAuthToken() !== null){

                // Check if OAuth token has been removed
                if(\in_array($cookie_token->getOAuthToken(), $removed_oauth_tokens)){
                    $output->writeln("[+] Remove Cookie token because OAuth token has been removed: <info>{$cookie_token->getToken()}</info>");
                    $this->em->remove($cookie_token);
                    continue;
                }
            }

            // Move cookie
            $cookie_token->setUser($main_user);
            $output->writeln("[+] Move Cookie token: <info>{$cookie_token->getToken()}</info>");
        }

        // Move IP logs
        $ip_log_count = 0;
        /** @var UserIpLog $ip_log */
        foreach($secondary_user->getIpLogs() as $ip_log) {
            $ip_log->setUser($main_user);
            $ip_log_count++;
        }
        if($ip_log_count > 0){
            $output->writeln("[+] Move <info>{$ip_log_count}</info> IP logs");
        }

        // Move User notifications
        $notification_count = 0;
        /** @var UserNotification $notification */
        foreach($secondary_user->getNotifications() as $notification){
            $notification->setUser($main_user);
            $notification_count++;
        }
        if($notification_count > 0){
            $output->writeln("[+] Move <info>{$notification_count}</info> notifications");
        }

        // Remove User notification settings
        /** @var UserNotificationSetting $notification_setting */
        foreach($secondary_user->getNotificationSettings() as $notification_setting){
            $this->em->remove($notification_setting);
        }
        $output->writeln("[+] Remove <info>all</info> notification settings");

        // Remove all password reset tokens
        /** @var UserPasswordResetToken $reset_token */
        foreach($secondary_user->getPasswordResetTokens() as $reset_token){
            $this->em->remove($reset_token);
        }
        $output->writeln("[+] Remove <info>all</info> password reset tokens");

        // Move workshop items
        /** @var WorkshopItem $item */
        foreach($secondary_user->getWorkshopItems() as $item){
            $item->setSubmitter($main_user);
            $output->writeln("[+] Move workshop item: <info>{$item->getName()}</info>");
        }

        // Move workshop comments
        $comment_count = 0;
        /** @var WorkshopComment $comment */
        foreach($secondary_user->getWorkshopComments() as $comment){
            $comment->setUser($main_user);
            $comment_count++;
        }
        if($comment_count > 0){
            $output->writeln("[+] Move <info>{$comment_count}</info> comments");
        }

        // Move workshop comment reports
        $comment_report_count = 0;
        /** @var WorkshopCommentReport $report */
        foreach($secondary_user->getWorkshopCommentReports() as $report){
            $report->setUser($main_user);
            $comment_report_count++;
        }
        if($comment_report_count > 0){
            $output->writeln("[+] Move <info>{$comment_report_count}</info> comment reports");
        }

        // Move workshop ratings
        /** @var WorkshopRating $rating */
        foreach($secondary_user->getWorkshopRatings() as $rating){

            $removed = false;

            /** @var WorkshopRating $main_rating */
            foreach($main_user->getWorkshopRatings() as $main_rating){
                if($main_rating->getItem() == $rating->getItem()){

                    $new_rating = \floor(
                        ($main_rating->getScore() + $rating->getScore()) / 2
                    );

                    if($main_rating->getScore() !== $new_rating){
                        $output->writeln("[+] Change rating from <info>{$main_rating->getScore()}</info> to <info>{$new_rating}</info> for <info>{$main_rating->getItem()->getName()}</info>");
                        $main_rating->setScore($new_rating);
                    }

                    $this->em->remove($rating);
                    $removed = true;
                    break;
                }
            }

            if($removed == false){
                $output->writeln("[+] Move rating for <info>{$rating->getItem()->getName()}</info>");
                $rating->setUser($main_user);
            }
        }

        // Move workshop difficulty ratings
        /** @var WorkshopDifficultyRating $rating */
        foreach($secondary_user->getWorkshopDifficultyRatings() as $diff_rating){

            $removed = false;

            /** @var WorkshopDifficultyRating $main_rating */
            foreach($main_user->getWorkshopDifficultyRatings() as $main_diff_rating){
                if($main_diff_rating->getItem() == $diff_rating->getItem()){

                    $new_rating = \floor(
                        ($main_diff_rating->getScore() + $diff_rating->getScore()) / 2
                    );

                    if($main_diff_rating->getScore() !== $new_rating){
                        $output->writeln("[+] Change difficulty rating from <info>{$main_diff_rating->getScore()}</info> to <info>{$new_rating}</info> for <info>{$main_diff_rating->getItem()->getName()}</info>");
                        $main_diff_rating->setScore($new_rating);
                    }

                    $this->em->remove($diff_rating);
                    $removed = true;
                    break;
                }
            }

            if($removed == false){
                $output->writeln("[+] Move difficulty rating for <info>{$diff_rating->getItem()->getName()}</info>");
                $diff_rating->setUser($main_user);
            }
        }

        // Finally remove old user
        $this->em->remove($secondary_user);

        // Ask whether or not this is correct
        $output->writeln("");
        $question = new ConfirmationQuestion("[?] Do you want to merge <info>{$secondary_user->getUsername()}</info> into <info>{$main_user->getUsername()}</info>? [y/n] ", false);
        /** @var HelperInterface */
        $helper = $this->getHelper('question');
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        // Flush changes to DB
        $this->em->flush();
        $output->writeln("[+] Merged '{$secondary_username}' into '{$main_username}'!");

        // Remove avatar
        if($avatar_file_to_remove !== null){
            $path = Config::get('storage.path.avatar') . '/' . $avatar_file_to_remove;
            if(\file_exists($path)){
                if(FileHelper::delete($path)){
                    $output->writeln("[+] Avatar deleted: <info>{$path}</info>");
                } else {
                    $output->writeln("[-] Failed to remove avatar: <info>{$path}</info>");
                }
            }
        }

        // Remove small avatar
        if($small_avatar_file_to_remove !== null){
            $path = Config::get('storage.path.avatar') . '/' . $small_avatar_file_to_remove;
            if(\file_exists($path)){
                if(FileHelper::delete($path)){
                    $output->writeln("[+] Avatar deleted: <info>{$path}</info>");
                } else {
                    $output->writeln("[-] Failed to remove avatar: <info>{$path}</info>");
                }
            }
        }

        // Success
        return Command::SUCCESS;
    }
}
