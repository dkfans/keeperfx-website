<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Xenokore\Utility\Helper\StringHelper;

class WikiController {

    private const WIKI_ROOT = APP_ROOT . '/var/wiki';

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
        ?string $page = null,
    ){
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
        foreach(\glob(self::WIKI_ROOT . '/*.md') as $file_path){
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

        // Get and handle 'sidebar' contents
        $sidebar_contents = \file_get_contents(self::WIKI_ROOT . '/_Sidebar.md');
        $sidebar_contents = $this->makeGithubUrlsLowercase($sidebar_contents);
        $sidebar_contents = $this->fixMarkdownHeaderTagsSEO($sidebar_contents);
        $sidebar_contents = \preg_replace('/\[(.+)\]\((.+)\)/', '[$1](/wiki/$2)', $sidebar_contents); // Replace sidebar URLs (/home -> /wiki/home)

        // Render
        $response->getBody()->write(
            $twig->render('wiki.html.twig', [
                'wiki' => [
                    'page_title'       => $page_title,
                    'page_contents'    => $page_contents,
                    'sidebar_contents' => $sidebar_contents
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

}
