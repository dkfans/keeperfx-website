<?php

namespace App;

use App\Enum\MailStatus;

use App\Entity\Mail;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment as TwigEnvironment;

class Mailer {

    public function __construct(
        private EntityManager $em,
    ) {}

    /**
     * Create and add a mail to the mailer queue
     *
     * @param string $receiver
     * @param string $subject
     * @param string $contents
     * @return false|integer      The id of the mail on success. -1 if not persisted to DB yet, False on failure
     */
    public function createMailInQueue(string $receiver, string $subject, string $body, ?string $html_body = null, bool $flush_db = true): int|false
    {
        try {
            $mail = new Mail();
            $mail->setReceiver($receiver);
            $mail->setSubject($subject);
            $mail->setBody($body);
            $mail->setHtmlBody($html_body);
            $mail->setStatus(MailStatus::NOT_SENT_YET);

            $this->em->persist($mail);

            if($flush_db){
                $this->em->flush();
            }

        } catch (\Exception $ex) {
            return false;
        }

        return $flush_db ? $mail->getId() : -1;
    }

    public function createMailForUser(User $user, string $subject, string $body, ?string $html_body = null, bool $flush_db = true): int|false
    {
        if($user->getEmail() === null){
            return false;
        }

        return $this->createMailInQueue($user->getEmail(), $subject, $body, $html_body, $flush_db);
    }

    public function sendMail(string $receiver, string $subject, string $body, ?string $html_body = null): bool
    {
        return $this->createPhpMailerInstanceWithData($receiver, $subject, $body, $html_body)->send();
    }

    public function getAllPendingMailsInQueue(): array
    {
        return $this->em->getRepository(Mail::class)->findBy(['status' => MailStatus::NOT_SENT_YET]);
    }

    // public function createPhpMailerInstanceForMailInQueue(int $id): ?PHPMailer
    // {
    //     $mail = $this->em->getRepository(Mail::class)->find($id);

    //     if(!$mail){
    //         return null;
    //     }

    //     return $this->createPhpMailerInstanceFromEntity($mail);
    // }

    public function createPhpMailerInstanceFromEntity(Mail $mail): PHPMailer
    {
        return $this->createPhpMailerInstanceWithData($mail->getReceiver(), $mail->getSubject(), $mail->getBody(), $mail->getHtmlBody());
    }

    public function createPhpMailerInstance(): PHPMailer
    {
        $php_mailer = new PHPMailer(true);
        $php_mailer->isSMTP();
        $php_mailer->Host     = $_ENV['APP_SMTP_HOST'];
        $php_mailer->SMTPAuth = (bool)$_ENV['APP_SMTP_AUTH'];
        $php_mailer->Username = $_ENV['APP_SMTP_USERNAME'];
        $php_mailer->Password = $_ENV['APP_SMTP_PASSWORD'];
        $php_mailer->Port     = (int) $_ENV['APP_SMTP_PORT'];

        if((bool)$_ENV['APP_SMTP_TLS']){
            $php_mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $php_mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        if((bool)$_ENV['APP_SMTP_VERIFY_CERT'] === true)
        {
            $php_mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ];
        }
        else {
            $php_mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                ]
            ];
        }

        $php_mailer->setFrom($_ENV['APP_SMTP_FROM_ADDRESS'], $_ENV['APP_SMTP_FROM_NAME']);

        return $php_mailer;
    }

    public function createPhpMailerInstanceWithData(string $receiver, string $subject, string $body, ?string $html_body = null): PHPMailer
    {

        $mail = $this->createPhpMailerInstance();

        $mail->Subject = $subject;
        $mail->addAddress($receiver);

        // Handle HTML mail
        if($html_body !== null){
            $mail->isHTML(true);
            $mail->Body    = $html_body;
            $mail->AltBody = $body;
        } else {
            // No HTML mail
            $mail->Body = $body;
        }

        return $mail;
    }
}
