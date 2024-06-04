<?php

namespace App\Controller;

use App\Enum\MailStatus;

use App\Entity\Mail;

use App\Mailer;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmailController {

    /**
     * Ajax endpoint to send email directly.
     *
     * Mostly useful for sending a mail while the user is using the website.
     * Most notably the email verification email.
     */
    public function sendEmail(
        Request $request,
        Response $response,
        EntityManager $em,
        Mailer $mailer,
        Session $session,
        int $id,
    ){

        // Get the mail
        $mail = $em->getRepository(Mail::class)->findOneBy([
            'id'     => $id,
            'status' => MailStatus::NOT_SENT_YET,
        ]);
        if(!$mail){
            $response->getBody()->write(
                \json_encode([
                    'success'=> false,
                    'error' => 'MAIL_NOT_FOUND'
                ])
            );
            return $response;
        }

        // Instantly update status for mail
        $mail->setStatus(MailStatus::SENDING);
        $em->flush();

        try {

            // Create and send mail
            $php_mailer = $mailer->createPhpMailerInstanceFromEntity($mail);
            $php_mailer->send();

            // Update status of email
            $mail->setStatus(MailStatus::SENT);
            $em->flush();

            // Remove send mail action from session
            if(!empty($session['send_mail']))
            {
                $session['send_mail'] = -1;
                unset($session['send_mail']);
            }

        } catch (\Exception $ex) {

            // Try sending again later
            $mail->setStatus(MailStatus::NOT_SENT_YET);
            $em->flush();

            // Return failure
            $response->getBody()->write(
                \json_encode([
                    'success'=> false,
                    'error' => 'FAILED_TO_SEND_MAIL'
                ])
            );
            return $response;
        }

        // Return success
        $response->getBody()->write(
            \json_encode([
                'success'=> true,
            ])
        );
        return $response;
    }

}
