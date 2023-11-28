<?php

namespace CraigPotter\Barstool\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraigPotter\Barstool\Barstool
 */
class Barstool extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CraigPotter\Barstool\Barstool::class;
    }
}
