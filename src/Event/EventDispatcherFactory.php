<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Event;

use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcherFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $listeners = $container->get(ListenerProviderInterface::class);
        $stdoutLogger = $container->get(StdoutLoggerInterface::class);

        return new EventDispatcher($listeners, $stdoutLogger);
    }
}
