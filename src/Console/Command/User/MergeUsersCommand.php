<?php

namespace App\Console\Command\User;

use App\Entity\User;
use App\Entity\UserOAuthToken;
use Doctrine\ORM\EntityManager;

//use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Enum\UserRole;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use App\Entity\UserCookieToken;

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
            $output->writeln("[-] Main account with username '{$main_username}' not found");
            return Command::FAILURE;
        }

        // Get secondary user
        /** @var ?User $secondary_user */
        $secondary_user = $this->em->getRepository(User::class)->findOneBy(['username' => $secondary_username]);
        if($secondary_user === null){
            $output->writeln("[-] Secondary account with username '{$secondary_username}' not found");
            return Command::FAILURE;
        }

        // Show info
        $output->writeln("");
        $output->writeln("[>] <info>Account 1</info>:");
        $output->writeln("[>]");
        $output->writeln('[>]   Username:            <info>' . $main_user->getUsername() . '</info>');
        $output->writeln('[>]   Email:               <info>' . ($main_user->getEmail() ?? '-') . '</info>');
        $output->writeln('[>]   Registration date:   <info>' . $main_user->getCreatedTimestamp()->format('Y-m-d G:i') . '</info>');
        $output->writeln('[>]   Workshop items:      <info>' . $main_user->getWorkshopItems()->count() . '</info>');
        $output->writeln('[>]   Workshop ratings:    <info>' . $main_user->getWorkshopRatings()->count() . '</info>');
        $output->writeln('[>]   Workshop comments:   <info>' . $main_user->getWorkshopComments()->count() . '</info>');
        $output->writeln("");
        $output->writeln("[>] <info>Account 2</info>:");
        $output->writeln("[>]");
        $output->writeln('[>]   Username:            <info>' . $secondary_user->getUsername() . '</info>');
        $output->writeln('[>]   Email:               <info>' . ($secondary_user->getEmail() ?? '-') . '</info>');
        $output->writeln('[>]   Registration date:   <info>' . $secondary_user->getCreatedTimestamp()->format('Y-m-d G:i') . '</info>');
        $output->writeln('[>]   Workshop items:      <info>' . $secondary_user->getWorkshopItems()->count() . '</info>');
        $output->writeln('[>]   Workshop ratings:    <info>' . $secondary_user->getWorkshopRatings()->count() . '</info>');
        $output->writeln('[>]   Workshop comments:   <info>' . $secondary_user->getWorkshopComments()->count() . '</info>');
        $output->writeln("");

        // Ask whether or not this is correct
        $question = new ConfirmationQuestion('[?] Do you want to merge Account #2 into Account #1? [y/n] ', false);
        /** @var HelperInterface */
        $helper = $this->getHelper('question');
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        // Move email
        if($main_user->getEmail() === null && $secondary_user->getEmail() !== null){
            $main_user->setEmail($secondary_user->getEmail());
            $main_user->setEmailVerified($secondary_user->isEmailVerified());
        }

        // Move possible email verification
        if($secondary_user->getEmailVerification() !== null){
            $secondary_user->getEmailVerification()->setUser($main_user);
        }

        // Set registration date to earliest registered account
        if($secondary_user->getCreatedTimestamp() < $main_user->getCreatedTimestamp()){
            $main_user->setCreatedTimestamp($secondary_user->getCreatedTimestamp());
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
                    $already_has_token = true;
                    break;
                }
            }

            // Move token to main user
            if($already_has_token == false){
                $token->setUser($main_user);
            }

            // Remove token because main user already has this service linked
            if($already_has_token == true){
                $this->em->remove($token);
                $removed_oauth_tokens[] =  $token;
            }
        }

        // Move cookie tokens
        /** @var UserCookieToken $cookie_token */
        foreach($secondary_user->getCookieTokens() as $cookie_token){

            // Check if cookie is linked to OAuth token
            if($cookie_token->getOAuthToken() !== null){

                // Check if OAuth token has been removed
                if(\in_array($cookie_token->getOAuthToken(), $removed_oauth_tokens)){
                    $this->em->remove($cookie_token);
                    continue;
                }
            }

            // Move cookie
            $cookie_token->setUser($main_user);
        }

        // Move IP logs

        // Move User notifications

        // Move User notification settings

        // Remove all password reset tokens

        // Move workshop items

        // Move workshop comments

        // Move workshop comment reports

        // Move workshop difficulty ratings

        // Flush changes to DB
        $this->em->flush();

        // Success
        $output->writeln("[+] Merged '{$secondary_username}' into '{$main_username}'!");
        return Command::SUCCESS;
    }
}
