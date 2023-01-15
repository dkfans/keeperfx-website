<?php

namespace App\Console\Command\Controller;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ControllerCreateCommand extends Command
{
    protected function configure()
    {
        $this->setName("controller:create")
            ->setDescription("Create a new blank controller")
            ->addArgument('name', InputArgument::REQUIRED, 'Controller name (without the Controller affix)')
            ->addOption('twig', '-t', InputOption::VALUE_NONE, 'Add Twig');
    }

    protected function execute(Input $input, Output $output)
    {
        $controller_dir = APP_ROOT . '/controllers';

        $controller_name = \ucfirst($input->getArgument('name')) . 'Controller';

        $output->writeln("[>] Controller name: <comment>{$controller_name}</comment>");

        $file_path = $controller_dir . '/' . $controller_name . '.php';

        if(\file_exists($file_path)){
            $output->writeln("[-] <error>{$file_path}</error> ALREADY EXISTS");
            return Command::FAILURE;
        }

        $str_twig1 = '';
        $str_twig2 = '';
        $str_contents = '$response->getBody()->write(\'Controller working!\');' . PHP_EOL .
                '        return $response;';

        if($input->getOption('twig')){

            $output->writeln("[>] Adding Twig");

            $str_twig1 = PHP_EOL . 'use Twig\Environment as TwigEnvironment;';
            $str_twig2 = ',' . PHP_EOL . '        TwigEnvironment $twig';
            $str_contents = '$response->getBody()->write(' . PHP_EOL .
                '            $twig->render(\'template.html.twig\')' . PHP_EOL .
                '        );' . PHP_EOL . PHP_EOL .
                '        return $response;';
        }

        $contents = <<<EOT
<?php

namespace App\Controller;
$str_twig1
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class $controller_name {

    public function index(
        Request \$request,
        Response \$response$str_twig2
    ){
        $str_contents
    }

}

EOT;

    if(!\file_put_contents($file_path, $contents)){
        $output->writeln("[-] <error>{$file_path}</error> CREATION FAILED");
        return Command::FAILURE;
    }

    $output->writeln("[+] <info>{$file_path}</info> CREATED!");
    $output->writeln("[>] Done!");

        return Command::SUCCESS;
    }
}
