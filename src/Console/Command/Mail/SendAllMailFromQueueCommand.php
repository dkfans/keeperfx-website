<?php

namespace App\Console\Command\Mail;

use App\Enum\MailStatus;
use App\Mailer;
use Doctrine\ORM\EntityManager;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Entity\Mail;

class SendAllMailFromQueueCommand extends Command
{
    public function __construct(
        private Mailer $mailer,
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("mail:send-queue-all")
            ->setDescription("Send all mails that are in the queue");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Checking for mails to send...");

        $mails = $this->mailer->getAllPendingMailsInQueue();
        $mail_count = \count($mails);

        if($mail_count === 0){
            $output->writeln("[+] No mails in queue");
            return Command::SUCCESS;
        } else {
            $output->writeln("[+] {$mail_count} mails in queue");
        }

        foreach($mails as $mail_loop_entity)
        {
            // Make sure mail has a valid receiver email
            $email = $mail_loop_entity->getReceiver();
            if($email === null || \filter_var($email, FILTER_VALIDATE_EMAIL) === false){

                // Remove from DB
                $output->writeln("[!] Removing #{$mail_loop_entity->getId()} because it has an invalid email address");
                $this->em->remove($mail_loop_entity);
                $this->em->flush();
                continue;
            }

            // Get mail again from DB to make sure it's not being sent by another process
            // Make sure status of mail did not change in the mean time
            $mail = $this->em->getRepository(Mail::class)->findOneBy([
                'id'     => $mail_loop_entity->getId(),
                'status' => MailStatus::NOT_SENT_YET,
            ]);
            if(!$mail){
                $output->writeln("[?] The status of mail #{$mail_loop_entity->getId()} was changed by another process...");
                continue;
            }

            // Instantly update status for mail
            // TODO: change to "SENT" after
            $mail->setStatus(MailStatus::SENDING);
            $this->em->flush();

            try {

                // Create and send mail
                $php_mailer = $this->mailer->createPhpMailerInstanceFromEntity($mail);
                $php_mailer->send();

                $output->writeln("[+] #{$mail->getId()} -> SENT! <info>{$mail->getSubject()}</info>");

            } catch (\Exception $ex) {

                $output->writeln("[-] Failed to send mail #{$mail->getId()} -> {$ex->getMessage()}");

                // Try sending again later
                $mail->setStatus(MailStatus::NOT_SENT_YET);
                $this->em->flush();
            }
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
