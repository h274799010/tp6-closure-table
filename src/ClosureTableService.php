<?php

namespace hs\ClosureTable;


use hs\ClosureTable\Console\MakeCommand;
use think\Service;

/**
 * ClosureTable service provider
 *
 * @package hs\ClosureTable
 */
class ClosureTableService extends Service
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('closuretable.make', static function ($app) {
            return $app[MakeCommand::class];
        });

        $this->commands(MakeCommand::class);
    }

}
