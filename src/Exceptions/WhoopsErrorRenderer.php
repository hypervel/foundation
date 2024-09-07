<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Exceptions;

use Hyperf\Context\Context;
use Hyperf\Context\RequestContext;
use Hyperf\Contract\SessionInterface;
use SwooleTW\Hyperf\Foundation\Exceptions\Contracts\ExceptionRenderer;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\RunInterface;

class WhoopsErrorRenderer implements ExceptionRenderer
{
    public function render(Throwable $throwable): string
    {
        $whoops = new Run();
        $whoops->pushHandler(
            $this->setupHandler(new PrettyPageHandler())
        );
        $whoops->allowQuit(false);

        ob_start();
        $whoops->{RunInterface::EXCEPTION_HANDLER}($throwable);

        return ob_get_clean();
    }

    protected function setupHandler($handler)
    {
        $handler->handleUnconditionally(true);

        if (defined('BASE_PATH')) {
            $handler->setApplicationRootPath(BASE_PATH);
        }

        $request = RequestContext::get();
        $handler->addDataTableCallback('PSR7 Query', [$request, 'getQueryParams']);
        $handler->addDataTableCallback('PSR7 Post', [$request, 'getParsedBody']);
        $handler->addDataTableCallback('PSR7 Server', [$request, 'getServerParams']);
        $handler->addDataTableCallback('PSR7 Cookie', [$request, 'getCookieParams']);
        $handler->addDataTableCallback('PSR7 File', [$request, 'getUploadedFiles']);
        $handler->addDataTableCallback('PSR7 Attribute', [$request, 'getAttributes']);

        if ($session = Context::get(SessionInterface::class)) {
            $handler->addDataTableCallback('Hyperf Session', [$session, 'all']);
        }

        return $handler;
    }
}
