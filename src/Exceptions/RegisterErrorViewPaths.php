<?php

declare(strict_types=1);

namespace LaravelHyperf\Foundation\Exceptions;

use Hyperf\Collection\Collection;
use LaravelHyperf\Support\Facades\View;

class RegisterErrorViewPaths
{
    /**
     * Register the error view paths.
     */
    public function __invoke()
    {
        if (! View::getFacadeRoot()) {
            return;
        }

        View::replaceNamespace('errors', Collection::make(config('view.config.view_path'))->map(function ($path) {
            return "{$path}/errors";
        })->push(__DIR__ . '/views')->all());
    }
}
