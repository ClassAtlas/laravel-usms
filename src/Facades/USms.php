<?php

namespace ClassAtlas\USms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ClassAtlas\USms\USms
 */
class USms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ClassAtlas\USms\USms::class;
    }
}
