<?php

namespace App\Lib;

use Illuminate\Support\Collection;

/**
 * Used for share data between class, function, controller, model, etc
 */
class TemporaryDataManager extends Collection
{
    protected static $collections = [];
    public static function create($name = 'default', $starter = [])
    {
        if (!(static::$collections[$name] ?? false)) {
            static::$collections[$name] = new static($starter);
        }

        return static::$collections[$name];
    }
}
