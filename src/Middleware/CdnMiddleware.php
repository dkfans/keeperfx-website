<?php

namespace App\Middleware;

use App\CDN;
use App\Account;

use Compwright\PhpSession\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Xenokore\Utility\Helper\FileHelper;

class CdnMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CDN $cdn,
        private Account $account,
        private Session $session,
    ) {}

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only determine best CDN if user has not chosen one themselves
        if ($this->cdn->isUserChoice() === false) {

            // Check if we already figured out a good default CDN for them
            if (!empty($this->session['cdn'])) {
                $this->cdn->setCdn($this->session['cdn']);
            } else {

                // Check if we have a country IP database
                $database_file = __DIR__ . '/../../var/' . \basename($_ENV['APP_GEOIP_DATABASE']);
                if (FileHelper::isAccessible($database_file)) {
                    try {
                        // Find IP in GeoIP database
                        $reader  = new \MaxMind\Db\Reader($database_file);
                        $record  = $reader->get($request->getAttribute('ip_address'));

                        // Check if country code is found
                        $country_code = $record['country_code'] ?? null;
                        if ($country_code !== null) {

                            // Try and set the best CDN for this country
                            $this->cdn->setByCountryDefault($country_code);
                        }
                    } catch (\Exception $ex) {
                    }
                }

                // Remember current CDN so we don't need to lookup again later
                $this->session['cdn'] = $this->cdn->getCurrentId();
            }
        }

        $response = $handler->handle($request);
        return $response;
    }
}
