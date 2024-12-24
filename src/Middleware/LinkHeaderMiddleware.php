<?php

namespace App\Middleware;


use App\Config\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LinkHeaderMiddleware implements MiddlewareInterface {

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

        // Get Link headers
        $link_definitions = Config::get('slim.link_headers');
        if(\is_array($link_definitions) && \count($link_definitions) > 0){

            // Header data
            $link_string = '';

            // Add already existing definitions
            if($response->hasHeader('Link')){
                $link_string .= $response->getHeaderLine('Link');
                $link_string .= ', ';
            }

            // Loop trough each path
            $extra_links = [];
            foreach($link_definitions as $path => $options){

                // Check if options is a valid array
                if(!is_array($options)){
                    throw new \App\Config\ConfigException("Slim link header options is not an array: {$path} (slim.config.php)");
                }

                // Make sure 'rel' is set
                if(empty($options['rel']) || !is_string($options['rel'])){
                    throw new \App\Config\ConfigException("Slim link header is missing the 'rel' option: {$path} (slim.config.php)");
                }

                // Create string
                $string = "<{$path}>";
                foreach($options as $name => $val){
                    $string .= "; {$name}=\"{$val}\"";
                }

                // Add
                $extra_links[] = $string;
            }

            // Add to main header string
            $link_string .= \implode(", ", $extra_links);

            // Add to request
            $response = $response->withHeader('Link', $link_string);
        }


        // Return
        return $response;
    }
}
