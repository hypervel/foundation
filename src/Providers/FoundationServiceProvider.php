<?php

declare(strict_types=1);

namespace Hypervel\Foundation\Providers;

use Hyperf\Command\Event\FailToHandle;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Grammar;
use Hyperf\HttpServer\MiddlewareManager;
use Hypervel\Auth\Contracts\Factory as AuthFactoryContract;
use Hypervel\Container\Contracts\Container;
use Hypervel\Event\Contracts\Dispatcher;
use Hypervel\Foundation\Console\CliDumper;
use Hypervel\Foundation\Console\Kernel as ConsoleKernel;
use Hypervel\Foundation\Contracts\Application as ApplicationContract;
use Hypervel\Foundation\Http\Contracts\MiddlewareContract;
use Hypervel\Foundation\Http\HtmlDumper;
use Hypervel\Http\Contracts\RequestContract;
use Hypervel\Router\Contracts\UrlGenerator as UrlGeneratorContract;
use Hypervel\Support\ServiceProvider;
use Hypervel\Support\Uri;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\VarDumper\Caster\StubCaster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Throwable;

class FoundationServiceProvider extends ServiceProvider
{
    protected ConfigInterface $config;

    protected ConsoleOutputInterface $output;

    public function __construct(protected ApplicationContract $app)
    {
        $this->config = $app->get(ConfigInterface::class);
        $this->output = new ConsoleOutput();

        if ($app->hasDebugModeEnabled()) {
            $this->output->setVerbosity(ConsoleOutputInterface::VERBOSITY_VERBOSE);
        }
    }

    public function boot(): void
    {
        $this->setDefaultTimezone();
        $this->setInternalEncoding();
        $this->setDatabaseConnection();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->overrideHyperfConfigs();
        $this->listenCommandException();
        $this->registerUriUrlGeneration();

        $this->registerDumper();

        $this->callAfterResolving(RequestContract::class, function (RequestContract $request) {
            $request->setUserResolver(function (?string $guard = null) {
                return $this->app
                    ->get(AuthFactoryContract::class)
                    ->guard($guard)
                    ->user();
            });
        });
    }

    protected function listenCommandException(): void
    {
        $this->app->get(EventDispatcherInterface::class)
            ->listen(FailToHandle::class, function ($event) {
                if ($this->isConsoleKernelCall($throwable = $event->getThrowable())) {
                    $this->app->get(ConsoleKernel::class)
                        ->getArtisan()
                        ->renderThrowable($throwable, $this->output);
                }
            });
    }

    protected function isConsoleKernelCall(Throwable $exception): bool
    {
        foreach ($exception->getTrace() as $trace) {
            if (($trace['class'] ?? null) === ConsoleKernel::class
                && ($trace['function'] ?? null) === 'call') {
                return true;
            }
        }

        return false;
    }

    protected function setDatabaseConnection(): void
    {
        $connection = $this->config->get('database.default', 'mysql');
        $this->app->get(ConnectionResolverInterface::class)
            ->setDefaultConnection($connection);
    }

    protected function overrideHyperfConfigs(): void
    {
        $configs = [
            'app_name' => $this->config->get('app.name'),
            'app_env' => $this->config->get('app.env'),
            StdoutLoggerInterface::class . '.log_level' => $this->config->get('app.stdout_log_level'),
            'databases' => $connections = $this->config->get('database.connections'),
            'databases.migrations' => $migration = $this->config->get('database.migrations', 'migrations'),
            'databases.default' => $connections[$this->config->get('database.default')] ?? [],
            'databases.default.migrations' => $migration,
            'redis' => $this->getRedisConfig(),
        ];

        foreach ($configs as $key => $value) {
            if (! $this->config->has($key)) {
                $this->config->set($key, $value);
            }
        }

        $this->config->set('middlewares', $this->getMiddlewareConfig());
    }

    protected function getRedisConfig(): array
    {
        $redisConfig = $this->config->get('database.redis', []);
        $redisOptions = $redisConfig['options'] ?? [];
        unset($redisConfig['options']);

        return array_map(function (array $config) use ($redisOptions) {
            return array_merge($config, [
                'options' => $redisOptions,
            ]);
        }, $redisConfig);
    }

    protected function getMiddlewareConfig(): array
    {
        if ($middleware = $this->config->get('middlewares', [])) {
            foreach ($middleware as $server => $middlewareConfig) {
                $middleware[$server] = MiddlewareManager::sortMiddlewares($middlewareConfig);
            }
        }

        foreach ($this->config->get('server.kernels', []) as $server => $kernel) {
            if (! is_string($kernel) || ! is_a($kernel, MiddlewareContract::class, true)) {
                continue;
            }
            $middleware[$server] = array_merge(
                $this->app->get($kernel)->getGlobalMiddleware(),
                $middleware[$server] ?? [],
            );
        }

        return $middleware;
    }

    protected function registerUriUrlGeneration(): void
    {
        Uri::setUrlGeneratorResolver(
            fn () => $this->app->get(UrlGeneratorContract::class)
        );
    }

    protected function setDefaultTimezone(): void
    {
        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));
    }

    protected function setInternalEncoding(): void
    {
        mb_internal_encoding('UTF-8');
    }

    protected function registerDumper(): void
    {
        AbstractCloner::$defaultCasters[ConnectionInterface::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Container::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Dispatcher::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Grammar::class] ??= [StubCaster::class, 'cutInternals'];

        $basePath = $this->app->basePath();

        $compiledViewPath = $this->config->get('view.config.view_path');

        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

        match (true) {
            $format == 'html' => HtmlDumper::register($basePath, $compiledViewPath),
            $format == 'cli' => CliDumper::register($basePath, $compiledViewPath),
            $format == 'server' => null,
            $format && parse_url($format, PHP_URL_SCHEME) == 'tcp' => null,
            default => php_sapi_name() === 'cli' ? CliDumper::register($basePath, $compiledViewPath) : HtmlDumper::register($basePath, $compiledViewPath),
        };
    }
}
