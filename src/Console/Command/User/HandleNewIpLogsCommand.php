<?php

namespace App\Console\Command\User;

use App\Entity\User;
use App\Entity\UserIpLog;
use App\Entity\UserNotification;
use App\Entity\UserPasswordResetToken;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Enum\UserRole;

class HandleNewIpLogsCommand extends Command
{
    /** @var Container $container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("user:handle-new-ip-logs")
                ->setDescription("Handle new ip logs and get info about them");
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $last_seen_timestamp = new \DateTime();
        $last_seen_timestamp->modify('-7 days'); // TODO: make this configurable

        $result = $em->getRepository(UserIpLog::class)->createQueryBuilder('e')
            ->where('e.last_seen_timestamp > :last_seen_timestamp')
            ->andWhere('e.country IS NULL')
            ->setParameter('last_seen_timestamp', $last_seen_timestamp)
            ->getQuery()
            ->getResult();

        if(!$result){
            $output->writeln("[+] No IP's to handle");
        } else {

            $client = new \GuzzleHttp\Client(
                ['verify' => false] // Don't verify SSL connection
            );

            /** @var UserIpLog $ip_log */
            foreach($result as $ip_log){

                $ip = $ip_log->getIp();

                // Ignore localhost
                if($ip === "127.0.0.1"){
                    continue;
                }

                $output->writeln("[>] Looking up <info>{$ip}</info>");

                // Get info from API
                $res = $client->request('GET', 'http://ip-api.com/json/' . $ip . '?fields=status,message,countryCode,isp,proxy,hosting,query');
                $content = $res->getBody();
                if(!$content){
                    $output->writeln("[-] Failed to grab content");
                    continue;
                }

                // Decode JSON
                $json = \json_decode($content, true);
                if(!$json){
                    $output->writeln("[-] Failed to decode JSON");
                    continue;
                }

                // Make sure lookup is successful
                if(!isset($json['status']) || !\is_string($json['status']) || $json['status'] !== 'success'){
                    $output->writeln("[-] Failed to get info");
                    continue;
                }
                if(!isset($json['query']) || !\is_string($json['query']) || $json['query'] !== $ip){
                    $output->writeln("[-] Returned IP does not match?");
                    continue;
                }

                // Update IP log
                $ip_log->setHostName(\gethostbyaddr($ip));
                $ip_log->setCountry($json['countryCode'] ?? null);
                $ip_log->setIsp($json['isp'] ?? null);
                $ip_log->setIsProxy($json['proxy'] ?? null);
                $ip_log->setIsHosting($json['hosting'] ?? null);

                $output->writeln("[+] Updated <query>{$ip}</query>");
            }

            $em->flush();
        }

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }

}
