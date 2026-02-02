<?php

namespace App\Console\Command\Dev;

use App\Entity\User;
use App\Enum\UserRole;

use App\Entity\UserBio;
use App\Entity\UserIpLog;
use App\Entity\NewsArticle;
use App\Entity\WorkshopItem;
use App\Entity\UserOAuthToken;

use App\Enum\OAuthProviderType;
use App\Enum\WorkshopCategory;
use App\Workshop\WorkshopCache;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class CreateMockDataCommand extends Command
{
    const ISP_LIST = [
        'Comcast',
        'AT&T',
        'Verizon',
        'Spectrum',
        'Cox',
        'CenturyLink',
        'Frontier',
        'BT',
        'Deutsche Telekom',
        'Orange',
        'Vodafone',
        'Telstra',
        'Shaw',
        'Rogers',
        'Bell Canada'
    ];

    const NEWS_LIST = [
        'We have reached a new milestone',
        'Yay! Another news item',
        'KeeperFX is absolutely amazing',
        'Hello. This is dog',
        'Yani is the best',
    ];

    const WORKSHOP_TITLE_STRINGS = [
        'Imp',
        'Troll',
        'Demon',
        'Bile',
        'Wizard',
        'Magic',
        'Thing',
        'Keeper',
        'Dungeon',
        'Devil',
        'Lair',
        'Workshop',
        'Disaster',
        'Launcher',
        'Campaign',
        'Blaze',
        'Kingdom',
        'Sample',
        'Skill',
        'Level',
        'Turtle',
        'Assist',
        'Trap',
        'Creatures',
    ];

    public function __construct(
        private EntityManager $em,
        private WorkshopCache $workshop_cache,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("dev:generate-mock-data")
            ->setDescription("Generate fake data for use during development");
    }

    protected function execute(Input $input, Output $output)
    {
        // Create the Faker instance for mock data such as usernames, email addresses and text
        $faker = \Faker\Factory::create();

        // Use cheaper password hashing costs
        $_ENV['APP_PASSWORD_HASH'] = '2y';
        $_ENV['APP_PASSWORD_HASH_BCRYPT_COST'] = 7;

        // Make an admin if there isn't one yet
        $admin_user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        if (!$admin_user) {

            $admin_user = new User();
            $admin_user->setUsername('admin');
            $admin_user->setPassword('admin');
            $admin_user->setRole(UserRole::Admin);

            $admin_bio = new UserBio();
            $admin_bio->setBio($faker->realText());
            $admin_bio->setUser($admin_user);

            $this->em->persist($admin_user);
            $this->em->persist($admin_bio);

            $output->writeln("[+] Added <info>admin</info> user");
        }

        // Make users
        $users = [$admin_user];
        for ($i = 0; $i < 10; $i++) {

            $user = new User();
            $user->setUsername($faker->userName());
            $user->setPassword('password');

            // Most of them have email addresses
            if ($i % \random_int(3, 5) !== 0) {
                $user->setEmail($faker->safeEmail());

                // Most of them have their email addresses verified
                if ($i % \random_int(3, 4) !== 0) {
                    $user->setEmailVerified(true);
                }
            }

            $this->em->persist($user);

            // Most of them have a bio set
            if ($i % \random_int(3, 5) !== 0) {
                $bio = new UserBio();
                $bio->setUser($user);
                $bio->setBio($faker->realText());
                $this->em->persist($bio);
            }

            // Some of them have a discord linked
            if ($i % \random_int(3, 5) > 2) {
                $oauth = new UserOAuthToken();
                $oauth->setProviderType(OAuthProviderType::Discord);
                $oauth->setUid($faker->numerify('##################'));
                $oauth->setUser($user);
                $this->em->persist($oauth);
            }

            // Some of them have a twitch linked
            if ($i % \random_int(3, 5) > 2) {
                $oauth = new UserOAuthToken();
                $oauth->setProviderType(OAuthProviderType::Twitch);
                $oauth->setUid($faker->numerify('###########'));
                $oauth->setUser($user);
                $this->em->persist($oauth);
            }

            // Generate some IP logs
            for ($x = 0; $x < \random_int(1, 4); $x++) {
                $ip_log = new UserIpLog();
                $ip_log->setUser($user);
                if (\random_int(0, 1) == 0) {
                    $ip_log->setIp($faker->ipv4());
                } else {
                    $ip_log->setIp($faker->ipv6());
                }
                $ip_log->setCountry($faker->countryCode());
                $ip_log->setIsp($faker->randomElement(self::ISP_LIST));
                $ip_log->setIsProxy($faker->boolean(0.1));
                $ip_log->setIsHosting($faker->boolean(0.2));
                $ip_log->setHostName($faker->safeEmailDomain());

                $this->em->persist($ip_log);
            }

            $output->writeln("[+] Added user: <info>{$user->getUsername()}</info>");
            $users[] = $user;
        }

        foreach (self::NEWS_LIST as $news_title) {

            $article = new NewsArticle();
            $article->setTitle($news_title);
            $article->setAuthor($admin_user);
            $article->setContents($faker->paragraphs(\random_int(2, 6), true));
            $article->setExcerpt($faker->realText());

            $this->em->persist($article);

            $output->writeln("[+] Added news item: <info>{$article->getTitle()}</info>");
        }

        $workshop_items = [];
        for ($i = 0; $i < 30; $i++) {

            $workshop_item = new WorkshopItem();
            $workshop_item->setSubmitter($faker->randomElement($users));
            $workshop_item->setDescription($faker->paragraphs(\random_int(1, 3), true));
            $workshop_item->setInstallInstructions($faker->paragraphs(\random_int(1, 3), true));
            $workshop_item->setIsPublished($faker->boolean(95));
            $workshop_item->setIsBundledWithGame($faker->boolean(5));
            $workshop_item->setDifficultyRatingEnabled($faker->boolean(75));
            $workshop_item->setDownloadCount(\random_int(10, 500));

            if (\random_int(0, 10) < 2) {
                $workshop_item->setOriginalAuthor($faker->name());
                $workshop_item->setOriginalCreationDate($faker->dateTimeThisDecade());
            }

            // TODO: min_game_build

            $workshop_item->setName(
                \ucwords(
                    \join(
                        ' ',
                        $faker->randomElements(
                            self::WORKSHOP_TITLE_STRINGS,
                            \random_int(2, 3)
                        )
                    )
                )
            );

            $category = $faker->randomElement(WorkshopCategory::class);
            $workshop_item->setCategory($category);

            if ($category == WorkshopCategory::Map) {
                $workshop_item->setMapNumber(\random_int(250, 65000));
            }

            $this->em->persist($workshop_item);
            $workshop_items[] = $workshop_item;

            $output->writeln("[+] Added workshop item: <info>{$workshop_item->getName()}</info>");
        }

        // Save changes to database
        $this->em->flush();

        // Clear the workshop cache so it reflects the new data
        $this->workshop_cache->clearAllCachedBrowsePageData();

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
