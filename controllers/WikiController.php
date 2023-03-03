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
        string $page = 'Home'
    ){
        // Vars
        $valid         = false;
        $page_file     = $page . '.md';
        $page_filepath = self::WIKI_ROOT . '/' . $page_file;
        $page_title    = StringHelper::replace($page, ['-' => ' ']);

        // Make sure the file is valid
        foreach(\glob(self::WIKI_ROOT . '/*.md') as $file){
            if($page_file === \basename($file)){
                $valid = true;
            }
        }
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Get wiki page contents
        $page_contents = \file_get_contents($page_filepath);
        $page_contents = $this->fixMarkdownHeaderTagsSEO($page_contents);

        // Get sidebar contents
        $sidebar_contents = \file_get_contents(self::WIKI_ROOT . '/_Sidebar.md');
        $sidebar_contents = $this->fixMarkdownHeaderTagsSEO($sidebar_contents);

        // Replace sidebar URLs (/Home -> /wiki/Home)
        $sidebar_contents = \preg_replace('/\[(.+)\]\((.+)\)/', '[$1](/wiki/$2)', $sidebar_contents);

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

}
