<?php

namespace App\Console\Command\Mail;

use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class SendMailCommand extends Command
{
    protected function configure()
    {
        $this->setName("mail:send")
            ->setDescription("Send a mail using the configured SMTP server. (noreply@....)")
            ->addArgument('email', InputArgument::REQUIRED, 'Email address to send the mail to')
            ->addArgument('title', InputArgument::REQUIRED, 'Title of the email')
            ->addArgument('contents', InputArgument::REQUIRED, 'Content of the email');
    }

    protected function execute(Input $input, Output $output)
    {
        // Get email address
        $email = \rtrim((string) $input->getArgument('email'), ' \\/');
        if(!$email || \filter_var($email, \FILTER_VALIDATE_EMAIL) === false){
            $output->writeln("[-] Invalid email address");
            return Command::FAILURE;
        }

        // Get email title
        $title = \rtrim((string) $input->getArgument('title'), ' \\/');
        if(!$title){
            $output->writeln("[-] Invalid title");
            return Command::FAILURE;
        }

        // Get email body
        $contents = \rtrim((string) $input->getArgument('contents'), ' \\/');
        if(!$contents){
            $output->writeln("[-] Invalid body contents");
            return Command::FAILURE;
        }

        $output->writeln("[>] Sending email...");


        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host     = $_ENV['APP_SMTP_HOST'];
            $mail->SMTPAuth = (bool)$_ENV['APP_SMTP_AUTH'];
            $mail->Username = $_ENV['APP_SMTP_USERNAME'];
            $mail->Password = $_ENV['APP_SMTP_PASSWORD'];
            $mail->Port     = (int) $_ENV['APP_SMTP_PORT'];

            if((bool)$_ENV['APP_SMTP_TLS']){
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            //Recipients
            $mail->setFrom($_ENV['APP_SMTP_FROM_ADDRESS'], $_ENV['APP_SMTP_FROM_NAME']);
            $mail->addAddress($email);
            $mail->isHTML(false);
            $mail->Subject = $title;
            $mail->Body    = $contents;

            $mail->send();
            $output->writeln("[+] Mail sent!");
        } catch (\Exception $e) {
            $output->writeln("[-] Failed to send mail...");
            $output->writeln("[-] {$mail->ErrorInfo}");
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
