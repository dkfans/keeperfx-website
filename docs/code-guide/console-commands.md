Console Commands
================

Xenokore framework uses the Symfony Console library and comes with a set of preconfigured commands.

## Default Commands

```
 cache
  cache:clear                       Clear the cache directory
laradock
  laradock:setup                    Setup a Laradock stack for this project
  laradock:start                    Start the Laradock stack
  laradock:stop                     Stop the Laradock stack
 migrations
  migrations:current                Outputs the current version
  migrations:diff                   Generate a migration by comparing your current database to your mapping information.
  migrations:dump-schema            Dump the schema for your database to a migration.
  migrations:execute                Execute one or more migration versions up or down manually.
  migrations:generate               Generate a blank migration class.
  migrations:latest                 Outputs the latest version
  migrations:list                   Display a list of all available migrations and their status.
  migrations:migrate                Execute a migration to a specified version or the latest available version.
  migrations:rollup                 Rollup migrations by deleting all tracked versions and insert the one version that exists.
  migrations:status                 View the status of a set of migrations.
  migrations:sync-metadata-storage  Ensures that the metadata storage is at the latest version.
  migrations:version                Manually add and delete migration versions from the version table.
```

## Creating own commands

It's possible to add your own commands by extending the `Symfony\Component\Console\Command\Command` class.  
By default, if the class is created in a directory that's configured for autowiring (such as anything under `<APP_ROOT>/src`) you can define classes from the container in your constructor and they will be automatically available (example below).

You'll have to add your command to the command definitions here: `<APP_ROOT>/app/commands.php`.

For documentation on creating your own commands you should have a look at the official Symfony Console docs:  
https://symfony.com/doc/current/console.html#creating-a-command

### Example

```php
<?php

namespace App\Console\Command;

use Psr\Container\ContainerInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExampleCommand extends Command
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName("namespace:command")
            ->setDescription("Example command");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Example console command");

        // Do stuff ...

        return Command::SUCCESS;
    }
}
```

In `<APP_ROOT>/app/commands.php`:

```php
return [
    \App\Console\Command\ExampleCommand::class
    // ...
]
```

Now when you run `console namespace:command` it will execute your command.

On Windows you'll probably have to use `php ./console namespace:command`.
