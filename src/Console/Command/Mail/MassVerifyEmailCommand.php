<?php

namespace App\Console\Command\Mail;

use App\Entity\User;
use App\Mailer;
use Doctrine\ORM\EntityManager;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Entity\UserEmailVerification;

class MassVerifyEmailCommand extends Command
{
    public function __construct(
        private Mailer $mailer,
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("mail:mass-verify-email")
            ->setDescription("Send verification emails to everybody");
    }

    protected function execute(Input $input, Output $output)
    {
        $users = $this->em->getRepository(User::class)->findAll();

        foreach($users as $user)
        {

            if($user->getEmail() === null){
                continue;
            }

            // Create the verification in the DB
            $verification = new UserEmailVerification();
            $verification->setUser($user);
            $this->em->persist($verification);
            $this->em->flush();

            // Create a mail
            // TODO: add template functionality
            $email_body = "Please verify your email address for KeeperFX using the following link: " . PHP_EOL;
            $email_body .= APP_ROOT_URL . '/verify-email/' . $user->getId() . '/' . $verification->getToken();

            // Create the mail in the mail queue and return the mail ID or FALSE on failure
            $this->mailer->createMailForUser(
                $user,
                'Verify your email address',
                $email_body,
                null,
                true,
            );
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
