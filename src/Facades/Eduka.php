<?php

namespace Eduka\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class Eduka extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'eduka-helper';
    }
}
