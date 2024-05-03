<?php

namespace App\Middleware;

use App\Account;
use App\FlashMessage;
use Compwright\PhpSession\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * TODO: Fix this?
 *
 * This implementation does not work correctly when we are behind CloudFlare and are listening both on a IPv4 and a IPv6 addresses.
 * Some clients switch between them while our session is still active but the CloudFlare one isn't anymore.
 *
 * We can't really protect against this because if we would store both IPv4 and IPv6 and a user is only using IPv4,
 * then an attacker could bypass this protection and hijack the session while connecting from a IPv6 address.
 */
class UserSessionProtectionMiddleware implements MiddlewareInterface {

    /** @var ResponseFactory $response_factory */
    public $response_factory;

    /** @var Account $account */
    public $account;

    /** @var Session $session */
    public $session;

    /** @var FlashMessage $flash */
    public $flash;

    /**
     * Constructor
     *
     * @param ResponseFactory $response_factory
     * @param Account $account
     * @param Session $session
     * @param FlashMessage $flash
     */
    public function __construct(ResponseFactory $response_factory, Account $account, Session $session, FlashMessage $flash)
    {
        $this->response_factory = $response_factory;
        $this->account          = $account;
        $this->session          = $session;
        $this->flash            = $flash;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get IP
        $ip = $request->getAttribute('ip_address');

        // Only for logged in users (with a valid IP)
        if($this->account->isLoggedIn() && $ip !== null){

            // Check if the IP is not yet stored in the session
            if(empty($this->session['ip']) || \is_null($this->session['ip'])){

                // Remember IP address for this session
                $this->session['ip'] = $ip;

            } else {

                // Check if the IP has changed
                if($this->session['ip'] !== $ip){

                    // Forget the IP
                    $this->session['ip'] = null;

                    // Logout the user
                    $this->account->clearCurrentLoggedInUser();

                    // Show nice message to our end user
                    $this->flash->warning("Your session has been invalidated because your IP address has changed.");

                    // Redirect back to this page so a new session is created
                    return $this->response_factory->createResponse()
                        ->withHeader('Location', $request->getUri()->getPath())
                        ->withStatus(302);
                }
            }
        }

        // Continue handling the request
        $response = $handler->handle($request);
        return $response;
    }
}
