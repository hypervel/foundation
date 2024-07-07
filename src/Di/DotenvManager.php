<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Di;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\AdapterInterface;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

class DotenvManager
{
    protected static AdapterInterface $adapter;

    protected static Dotenv $dotenv;

    protected static array $cachedValues;

    public static function load(array $paths): void
    {
        if (isset(static::$cachedValues)) {
            return;
        }

        static::$cachedValues = static::getDotenv($paths)->load();
    }

    public static function reload(array $paths, bool $force = false): void
    {
        if (! isset(static::$cachedValues)) {
            static::load($paths);

            return;
        }

        $entries = static::getDotenv($paths, $force)->load();
        $deletedEntries = array_diff_key(static::$cachedValues, $entries);

        foreach ($deletedEntries as $deletedEntry => $value) {
            static::getAdapter()->delete($deletedEntry);
        }

        static::$cachedValues = $entries;
    }

    protected static function getDotenv(array $paths, bool $force = false): Dotenv
    {
        if (isset(static::$dotenv) && ! $force) {
            return static::$dotenv;
        }

        return static::$dotenv = Dotenv::create(
            RepositoryBuilder::createWithNoAdapters()
                ->addAdapter(static::getAdapter($force))
                ->make(),
            $paths
        );
    }

    protected static function getAdapter(bool $force = false): AdapterInterface
    {
        if (isset(static::$adapter) && ! $force) {
            return static::$adapter;
        }

        return static::$adapter = PutenvAdapter::create()->get();
    }
}
