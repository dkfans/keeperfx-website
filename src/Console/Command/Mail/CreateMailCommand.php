<?php

namespace App\Console\Command\Mail;

use App\Mailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class CreateMailCommand extends Command
{
    public function __construct(
        private Mailer $mailer
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("mail:create")
            ->setDescription("Create a mail that will be added to the queue for sending")
            ->addArgument('email', InputArgument::REQUIRED, 'Email address to send the mail to')
            ->addArgument('subject', InputArgument::REQUIRED, 'Subject of the email')
            ->addArgument('body', InputArgument::REQUIRED, 'Body of the email');
    }

    protected function execute(Input $input, Output $output)
    {
        // Get email address
        $email = \rtrim((string) $input->getArgument('email'), ' \\/');
        if(!$email || \filter_var($email, \FILTER_VALIDATE_EMAIL) === false){
            $output->writeln("[-] Invalid email address");
            return Command::FAILURE;
        }

        // Get email subject
        $subject = \rtrim((string) $input->getArgument('subject'), ' \\/');
        if(!$subject){
            $output->writeln("[-] Invalid subject");
            return Command::FAILURE;
        }

        // Get email body
        $body = \rtrim((string) $input->getArgument('body'), ' \\/');
        if(!$body){
            $output->writeln("[-] Invalid body");
            return Command::FAILURE;
        }

        $output->writeln("[>] Adding mail to queue...");

        $mail_id = $this->mailer->createMailInQueue($email, $subject, $body);

        if($mail_id !== false){
            $output->writeln("[>] Added! (id: {$mail_id})");
        } else {
            $output->writeln("[-] Failed to add mail to queue");
            return Command::FAILURE;
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
