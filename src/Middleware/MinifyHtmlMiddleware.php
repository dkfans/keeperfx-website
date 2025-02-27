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

class MinifyHtmlMiddleware implements MiddlewareInterface {

    /**
     * minify html content
     *
     * @param string $html
     * @return string
     */
    private function minifyHTML($html)
    {
        // $parser = \WyriHaximus\HtmlCompress\Factory::constructSmallest();
        // return $parser->compress($html);

        // TODO: fix the minifying
        // ISSUE: https://github.com/WyriHaximus/HtmlCompress/issues/168
        // https://github.com/voku/HtmlMin/issues/93
        return $html;
    }

    private function minifyResponse(ResponseInterface $response): ResponseInterface
    {
        $body = (string) $response->getBody();
        $minified = $this->minifyHTML($body);

        $stream = new \Slim\Psr7\Stream(fopen('php://temp', 'r+'));
        $stream->write($minified);

        return $response->withBody($stream);
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
        // Handle everything as normal
        $response = $handler->handle($request);

        // Check if minifying is enabled
        if(!isset($_ENV['APP_MINIFY_HTML']) || !(bool)$_ENV['APP_MINIFY_HTML']){
            return $response;
        }

        // Don't minify anything that is requested using AJAX
        // We don't need to minify JSON
        if($request->getHeaderLine('X-Requested-With') === "XMLHttpRequest"){
            return $response;
        }

        // Minify
        $values = $response->getHeader('content-type');
        if(is_array($values)){
            if(
                \count($values) === 0 ||
                (isset($values[0]) && $values[0] === 'plain/html')
            ){
                $response = $this->minifyResponse($response);
            }
        }

        // Return
        return $response;
    }
}
