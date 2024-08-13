<?php

namespace Eduka\Helpers;

use Illuminate\Support\ServiceProvider;

class HelpersServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('eduka-helper', function () {
            return new Eduka;
        });
    }
}
