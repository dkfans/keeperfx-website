<?php

namespace App\Controller;

use App\Enum\MailStatus;

use App\Entity\Mail;
use App\Mailer;
use Doctrine\ORM\EntityManager;

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
        // TODO: change to "SENT" after
        $mail->setStatus(MailStatus::SENDING);
        $em->flush();

        try {

            // Create and send mail
            $php_mailer = $mailer->createPhpMailerInstanceFromEntity($mail);
            $php_mailer->send();

        } catch (\Exception $ex) {

            // Try sending again later
            $mail->setStatus(MailStatus::NOT_SENT_YET);
            $em->flush();

            // Return failure
            $response->getBody()->write(
                \json_encode([
                    'success'=> false,
                    'error' => 'MAIL_NOT_FOUND'
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
