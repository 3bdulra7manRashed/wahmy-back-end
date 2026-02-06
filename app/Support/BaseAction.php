<?php

namespace App\Support;

abstract class BaseAction
{
    /**
     * Execute the action.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * static run method for convenience
     */
    public static function run(...$arguments)
    {
        return app(static::class)->handle(...$arguments);
    }
}
