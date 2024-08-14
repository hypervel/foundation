<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Testing\Concerns;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use SwooleTW\Hyperf\Foundation\Testing\Wormhole;

trait InteractsWithTime
{
    /**
     * Freeze time.
     *
     * @param null|callable $callback
     * @return mixed
     */
    public function freezeTime($callback = null)
    {
        return $this->travelTo(Carbon::now(), $callback);
    }

    /**
     * Freeze time at the beginning of the current second.
     *
     * @param null|callable $callback
     * @return mixed
     */
    public function freezeSecond($callback = null)
    {
        return $this->travelTo(Carbon::now()->startOfSecond(), $callback);
    }

    /**
     * Begin travelling to another time.
     */
    public function travel(int $value): Wormhole
    {
        return new Wormhole($value);
    }

    /**
     * Travel to another time.
     *
     * @param null|bool|Closure|DateTimeInterface|\Illuminate\Support\Carbon|string $date
     * @param null|callable $callback
     * @return mixed
     */
    public function travelTo($date, $callback = null)
    {
        Carbon::setTestNow($date);

        if ($callback) {
            return tap($callback($date), function () {
                Carbon::setTestNow();
            });
        }
    }

    /**
     * Travel back to the current time.
     *
     * @return DateTimeInterface
     */
    public function travelBack()
    {
        return Wormhole::back();
    }
}
