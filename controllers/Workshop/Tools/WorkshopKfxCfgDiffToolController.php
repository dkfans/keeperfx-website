<?php

namespace App\Controller\Workshop\Tools;

use App\FlashMessage;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;
use Xenokore\Utility\Helper\StringHelper;

/**
 * A tool to compare CFGs and show the differences.
 * This is useful for getting only updated properties from KeeperFX configs.
 */
class WorkshopKfxCfgDiffToolController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ){
        $response->getBody()->write(
            $twig->render('workshop/tools/kfx_cfg_diff_tool.html.twig')
        );

        return $response;
    }

    public function compare(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
    ){
        // Get post vars
        $post  = $request->getParsedBody();
        $left  = (string) $post['left'] ?? '';
        $right = (string) $post['right'] ?? '';

        // Make sure data is posted
        if(empty($left) || empty($right)){
            $flash->warning("Both the left and right side need to be given.");
            $response->getBody()->write(
                $twig->render('workshop/tools/kfx_cfg_diff_tool.html.twig')
            );
            return $response;
        }

        // Get .ini data
        $left_data  = $this->getCustomCfgTree($left);
        $right_data = $this->getCustomCfgTree($right);

        // Create differences
        $diff = [];
        foreach($right_data as $section => $properties)
        {
            foreach($properties as $property => $value)
            {
                // Add difference to diff if:
                // - the left side does not have the right side line
                // - or the right side has a different line
                if(
                    !isset($left_data[$section][$property])
                    || $left_data[$section][$property] !== $value
                ){
                    $diff[$section][$property] = $value;
                }
            }
        }

        // Add 'Name' to updated sections
        foreach($diff as $section => $properties)
        {
            // If name is already set in the right side, don't change it
            if(isset($diff[$section]['Name'])){
                continue;
            }

            // Move name from left side to right
            if(isset($left_data[$section]['Name']) && !empty($left_data[$section]['Name'])){
                $diff[$section]['Name'] = $left_data[$section]['Name'];
            }
        }

        // Move 'attributes->Name' for creature configs
        if(!empty($left_data['attributes']) && !empty($left_data['attributes']['Name'])){
            $diff = ['attributes' => ['Name' => $left_data['attributes']['Name']]] + $diff;
        }

        // Create diff string output
        $diff_output = "";
        foreach($diff as $section => $properties)
        {
            // Add section
            $diff_output .= "[{$section}]" . PHP_EOL;

            // Move 'Name' property to top if it exists
            if(!empty($properties['Name'])){
                $properties = ['Name' => $properties['Name']] + $properties;
            }

            // Add all the properties
            foreach($properties as $property => $value)
            {
                $diff_output .= "{$property} = {$value}" . PHP_EOL;
            }

            $diff_output .= PHP_EOL;
        }

        // Trim trailing newlines
        $diff_output = \rtrim($diff_output);

        // Output back to user
        $response->getBody()->write(
            $twig->render('workshop/tools/kfx_cfg_diff_tool.html.twig', [
                'diff_output' => $diff_output
            ])
        );
        return $response;
    }

    private function getCustomCfgTree(string $string)
    {
        $array = [];

        $current_section = null;

        // Loop trough all the lines in the string
        foreach(\preg_split("/\r\n|\n|\r/", $string) as $line)
        {

            // Ignore empty lines
            if(empty($line) || $line === '' || $line === ' '){
                continue;
            }

            // Ignore comments
            if(StringHelper::startsWith($line, [';','#','/']) === true){
                continue;
            }

            // Start a section
            if(\preg_match("/\[(.+)\].*?/", $line, $matches)){
                $current_section = $matches[1];
                $array[$matches[1]] = [];
                continue;
            }

            // We need to be in a section at this point
            if($current_section === null){
                continue;
            }

            // Everything needs to have a "=" in the config
            if(\str_contains($line, '=') === false){
                continue;
            }

            // Get the data and put it into the array
            if(\preg_match("/(\w+?)\s*\=\s*(.*)/", $line, $matches)){
                $array[$current_section][$matches[1]] = $matches[2];
            }
        }

        return $array;
    }
}
