<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\Database\Commands\Migrations\BaseCommand as MigrationBaseCommand;
use Hyperf\Database\Commands\Seeders\BaseCommand as SeederBaseCommand;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Model\Factory as DatabaseFactory;
use SwooleTW\Hyperf\Foundation\Console\ApplicationFactory;
use SwooleTW\Hyperf\Foundation\Console\Commands\ServerReloadCommand;
use SwooleTW\Hyperf\Foundation\Console\Commands\VendorPublishCommand;
use SwooleTW\Hyperf\Foundation\Console\Contracts\Schedule as ScheduleContract;
use SwooleTW\Hyperf\Foundation\Console\Scheduling\Schedule;
use SwooleTW\Hyperf\Foundation\Listeners\ReloadDotenvAndConfig;
use SwooleTW\Hyperf\Foundation\Model\FactoryInvoker;
use SwooleTW\Hyperf\Foundation\Queue\Console\QueueWorkCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ApplicationInterface::class => ApplicationFactory::class,
                DatabaseFactory::class => FactoryInvoker::class,
                ScheduleContract::class => Schedule::class,
            ],
            'listeners' => [
                ReloadDotenvAndConfig::class,
            ],
            'commands' => [
                QueueWorkCommand::class,
                ServerReloadCommand::class,
                VendorPublishCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'class_map' => [
                        Migration::class => __DIR__ . '/../class_map/Database/Migrations/Migration.php',
                        MigrationBaseCommand::class => __DIR__ . '/../class_map/Database/Commands/Migrations/BaseCommand.php',
                        SeederBaseCommand::class => __DIR__ . '/../class_map/Database/Commands/Seeders/BaseCommand.php',
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The configuration file of foundation.',
                    'source' => __DIR__ . '/../publish/app.php',
                    'destination' => BASE_PATH . '/config/autoload/app.php',
                ],
            ],
        ];
    }
}
