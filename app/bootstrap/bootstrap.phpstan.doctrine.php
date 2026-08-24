<?php

require __DIR__ . '/bootstrap.php';

/** @var Psr\Container\ContainerInterface $container */

return $container->get(Doctrine\ORM\EntityManager::class);
