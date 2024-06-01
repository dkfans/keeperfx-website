<?php

namespace App\Twig\Extension;

use App\Account;
use Compwright\PhpSession\Session;

/**
 * Email Verification Twig Extension.
 */
class EmailTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function __construct(
        private Session $session,
    ) {}

    public function getName(): string
    {
        return 'email_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'get_email_id_to_send',
                [$this, 'getEmailId']
            ),
        ];
    }

    public function getEmailId(): int
    {
        // Check if we need to create an ajax request on this page to send an email
        // We do it like this so the user does not need to wait a minute until it is checked in the backend
        if(isset($this->session->send_email)){
            if(\is_int($this->session->send_email)){

                // Remember ID to add to the output HTML page
                $id = $this->session->send_email;

                // Unset the mail from the session
                $this->session->send_email = false;
                unset($this->session->send_email);

                // Return to user
                return $id;
            }
        }

        return -1;
    }
}
