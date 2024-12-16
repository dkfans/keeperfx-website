<?php

namespace App\Controller;

use App\FlashMessage;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

use Xenokore\Utility\Helper\StringHelper;

class WikiController {

    private function fixMarkdownHeaderTagsSEO(string $content): string
    {
        return \preg_replace(
            [
                '/^\#{5} /m',
                '/^\#{4} /m',
                '/^\#{3} /m',
                '/^\#{2} /m',
                '/^\#{1} /m'
            ], [
                '###### ',
                '###### ',
                '##### ',
                '#### ',
                '### ',
            ], $content);
    }

    public function wikiPage(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        ?string $page = null,
    ){
        // Get wiki dir
        $wiki_dir = $_ENV['APP_WIKI_STORAGE'];
        if(empty($wiki_dir) || !\is_dir($wiki_dir) || !\is_readable($wiki_dir)){
            throw new HttpInternalServerErrorException($request, "wiki dir is not accessible");
        }

        // Redirect to the "/wiki/home" if no page is given
        if($page === null){
            $response = $response->withHeader('Location', '/wiki/home')->withStatus(302);
            return $response;
        }

        // Make sure wiki page URL is lowercase
        // Redirect the user if it isn't
        if(\strtolower($page) !== $page){
            $response = $response->withHeader('Location', '/wiki/' . \strtolower($page))->withStatus(302);
            return $response;
        }

        // Get the markdown file
        $file = null;
        foreach(\glob($wiki_dir . '/*.md') as $file_path){
            if(strtolower(\basename($file_path)) === strtolower($page) . '.md'){
                $file = $file_path;
                break;
            }
        }

        // Make sure file is found
        if(!$file){
            throw new HttpNotFoundException($request);
        }

        // Get the page title
        $page_title = StringHelper::replace(\basename(\substr($file, 0, -3)), ['-' => ' ']);

        // Get and handle 'wiki page' contents
        $page_contents = \file_get_contents($file);
        $page_contents = $this->makeGithubUrlsLowercase($page_contents);
        $page_contents = $this->fixMarkdownHeaderTagsSEO($page_contents);

        // TODO: markdown titles should be #hash-bang linkable

        // Get 'sidebar' contents
        $sidebar_contents = \file_get_contents($wiki_dir . '/_Sidebar.md');
        if($sidebar_contents === false){
            throw new \Exception("Sidebar markdown file not found");
        }

        // Get a nice array structure of the sidebar
        $sidebar = $this->getWikiURLStructure($sidebar_contents);

        // Render
        $response->getBody()->write(
            $twig->render('wiki.html.twig', [
                'wiki' => [
                    'page_title'    => $page_title,
                    'page_contents' => $page_contents,
                    'sidebar'       => $sidebar,
                ]
            ])
        );
        return $response;
    }

    private function makeGithubUrlsLowercase(string $string): string
    {
        return \preg_replace_callback("~\[(.+?)\]\((.+?)\)~", function($matches){

            // Only lowercase URLs without a slash
            // This should work against any absolute URLs as well as "subdirectory-URLs".
            if(\str_contains($matches[2], '/') === false){

                $url = $matches[2];

                // Handle hash-bang
                if(\str_contains($url, '#') === true){
                    $exp = explode('#', $url);
                    $url = strtolower($exp[0]) . '#' . $exp[1];
                } else {
                    $url = strtolower($url);
                }

                // Return the new markdown
                return \sprintf("[%s](%s)", $matches[1], $url);
            }

            return $matches[0];
        }, $string);
    }

    private function getWikiURLStructure(string $contents): array
    {
        $array = [];
        $current_menu = null;

        // Loop trough the sidebar menu contents
        $lines = \explode(PHP_EOL, $contents);
        foreach($lines as $line)
        {
            $line = \trim($line);

            // If this is an empty line we are not in a list
            if(empty($line)){
                $current_menu = null;
                continue;
            }

            // Ignore comments
            if(StringHelper::startsWith($line, '<!--')){
                continue;
            }

            // Ignore the sidebar title
            // The space after '##' is important
            if(StringHelper::startsWith($line, '##  ')){
                continue;
            }

            // If this is a menu title
            if(StringHelper::startsWith($line, '#### ')){
                $name = substr($line, 5);
                $current_menu = $name;
                continue;
            }

            // If this is not a item it will be a subitem
            if(StringHelper::startsWith($line, ['*', '-'])){

                // We need to be in a menu
                if($current_menu === null){
                    continue;
                }

                // Find the link and title
                if(!\preg_match('~\[(.+)\]\((.+)\)~', $line, $matches)){
                    continue;
                }

                $array[$current_menu][$matches[1]] = '/wiki/' . \strtolower($matches[2]);
            }
        }

        return $array;
    }

}
