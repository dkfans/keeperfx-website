<?php

namespace App\Console\Command\Cache;

use App\Config\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Psr\SimpleCache\CacheInterface;

use Xenokore\Utility\Helper\StringHelper;

class CacheClearCommand extends Command
{
    public const CACHE_DIR = APP_ROOT . '/cache';

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("cache:clear")
            ->setDescription("Clear the app cache and the cache directory")
            ->addOption('ignore-sessions', '-i', InputOption::VALUE_NONE, 'Ignore sessions');
    }

    protected function execute(Input $input, Output $output)
    {
        $current_user = \exec('whoami');
        $owning_user  = \get_current_user();

        if($current_user !== $owning_user){
            $output->writeln('[!] <error>Current user and script owner do not match!</error>');
            $output->writeln('[>] User executing the command: ' . $current_user);
            $output->writeln('[>] Script owner: ' . $owning_user);
            $output->writeln('[!] Running this command might result in permission errors.');

            $question = new ConfirmationQuestion('[?] Continue? [y/n] ', false);

            /** @var HelperInterface */
            $helper = $this->getHelper('question');
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $cache_adapter = Config::get('cache.adapter');
        $output->writeln("[>] Clearing cache: {$cache_adapter}");

        // Check if we need to ignore sessions in this cache
        // This makes it so users will not lose their logged in session or CSRF tokens
        if($input->getOption('ignore-sessions')){
            $output->writeln("[>] Ignoring sessions in cache");

            // Only ignore sessions in Redis cache
            if(Config::get('cache.adapter') === 'redis'){
                $output->writeln("[>] Adapter: redis");

                // Get the prefix
                $prefix = Config::get('cache.namespace') ?? Config::get('app.app_name');
                $output->writeln("[>] Prefix: $prefix");

                // Use Predis to connect to it as we can loop trough keys with this adapter
                $predis = new \Predis\Client($_ENV['APP_CACHE_REDIS_DSN']);

                // Loop trough all the keys in the redis cache
                foreach (new \Predis\Collection\Iterator\Keyspace($predis, $prefix . ':*') as $key) {

                    // Check if length of this key matches that of a session ID
                    $key_name = substr($key, strlen($prefix) + 1);
                    if(strlen($key_name) !== Config::get('session.sid_length')){
                        $predis->del($key);
                        continue;
                    }

                    // Get the data from the cache
                    $str = $predis->get($key);

                    // Make sure this isn't a Doctrine or Twig object
                    if(StringHelper::contains($str, 'Doctrine\\') || StringHelper::contains($str, 'Twig\\')){
                        $predis->del($key);
                        continue;
                    }

                    // Simple check to make sure this is unserializable
                    if(StringHelper::contains($str, 'a:') === false){
                        $predis->del($key);
                        continue;
                    }

                    // Remove some weird bytes from beginning of the data
                    $split = \explode('a:', $str);
                    \array_shift($split);
                    $new_str = 'a:' . \implode('a:', $split);

                    // Unserialize
                    try {
                        $arr = \unserialize($new_str);
                    } catch (\Exception $ex) {
                        $predis->del($key);
                        continue;
                    }

                    // Last check to make sure this is a user session
                    if(empty($arr['uid'])){
                        $predis->del($key);
                        continue;
                    }

                    $output->writeln("[>] Session kept: <info>$key_name</info>");

                }

                $output->writeln("[+] <info>CACHE CLEARED</info>");

            } else {
                $output->writeln("[-] Ignoring sessions in non redis caches is not implemented yet");
                return Command::FAILURE;
            }
        } else {
            $output->writeln("[>] Clearing <info>FULL</info> cache");
            if($this->cache->clear()){
                $output->writeln("[+] <info>CACHE CLEARED</info>");
            } else {
                $output->writeln("[-] <error>CACHE CLEAR FAILED</error>");
                return Command::FAILURE;
            }
        }

        $output->writeln('[>] Clearing cache file directory: ' . self::CACHE_DIR);

        $iterator = new \RecursiveDirectoryIterator(self::CACHE_DIR, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        $dir_count  = 0;
        $file_count = 0;
        $dir_count_deleted  = 0;
        $file_count_deleted = 0;

        /** @var \SplFileInfo $file */
        foreach($files as $file) {
            $path = $file->getRealPath();

            if($file->getFilename() === '.gitignore'){
                continue;
            }

            if ($file->isDir()){
                $dir_count++;
                if(@\rmdir($path)){
                    $dir_count_deleted++;
                    $output->writeln("[+] DIR: <info>{$path}</info> DELETED");
                } else {
                    $output->writeln("[-] DIR: <error>{$path}</error> FAILED");
                }
            } else {
                $file_count++;
                if(@\unlink($path)){
                    $file_count_deleted++;
                    $output->writeln("[+] FILE: <info>{$path}</info> DELETED");
                } else {
                    $output->writeln("[-] FILE: <error>{$path}</error> FAILED");
                }
            }
        }

        $output->writeln("[>] Done!");
        $output->writeln("[>] Files deleted: {$file_count_deleted}/{$file_count}");
        $output->writeln("[>] Directories removed: {$dir_count_deleted}/{$dir_count}");

        return Command::SUCCESS;
    }
}
