<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Model;

use Faker\Factory as FakerFactory;
use Psr\Container\ContainerInterface;

class FactoryInvoker
{
    public function __invoke(ContainerInterface $container)
    {
        $factory = new Factory(
            FakerFactory::create('en_US')
        );

        if (is_dir($path = database_path('factories') ?: '')) {
            $factory->load($path);
        }

        return $factory;
    }
}
