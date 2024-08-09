<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Http\Contracts;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use SwooleTW\Hyperf\Dispatcher\ParsedMiddleware;

interface Kernel
{
    /**
     * Get middleware array for request.
     */
    public function getMiddlewareForRequest(ServerRequestInterface $request): array;

    /**
     * Parse string middleware to ParsedMiddleware.
     */
    public function parseMiddleware(string $middleware): ParsedMiddleware;

    /**
     * Determine if the kernel has a given middleware.
     */
    public function hasMiddleware(string $middleware): bool;

    /**
     * Add a new middleware to the beginning of the stack if it does not already exist.
     *
     * @return $this
     */
    public function prependMiddleware(string $middleware): static;

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * @return $this
     */
    public function pushMiddleware(string $middleware): static;

    /**
     * Prepend the given middleware to the given middleware group.
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function prependMiddlewareToGroup(string $group, string $middleware): static;

    /**
     * Append the given middleware to the given middleware group.
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function appendMiddlewareToGroup(string $group, string $middleware): static;

    /**
     * Prepend the given middleware to the middleware priority list.
     *
     * @return $this
     */
    public function prependToMiddlewarePriority(string $middleware): static;

    /**
     * Append the given middleware to the middleware priority list.
     *
     * @return $this
     */
    public function appendToMiddlewarePriority(string $middleware): static;

    /**
     * Get the priority-sorted list of middleware.
     */
    public function getMiddlewarePriority(): array;

    /**
     * Get the application's global middleware.
     */
    public function getGlobalMiddleware(): array;

    /**
     * Set the application's global middleware.
     *
     * @return $this
     */
    public function setGlobalMiddleware(array $middleware): static;

    /**
     * Get the application's route middleware groups.
     */
    public function getMiddlewareGroups(): array;

    /**
     * Set the application's middleware groups.
     *
     * @return $this
     */
    public function setMiddlewareGroups(array $groups): static;

    /**
     * Get the application's route middleware aliases.
     */
    public function getMiddlewareAliases(): array;

    /**
     * Set the application's route middleware aliases.
     *
     * @return $this
     */
    public function setMiddlewareAliases(array $aliases): static;

    /**
     * Set the application's middleware priority.
     *
     * @return $this
     */
    public function setMiddlewarePriority(array $priority): static;
}
