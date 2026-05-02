<?php

namespace App\Providers;

use App\Contracts\LockServiceInterface;
use App\Contracts\RedisServerInterface;
use App\Service\RedisLockService;
use App\Service\RedisServer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RedisServerInterface::class, RedisServer::class);
        $this->app->bind(LockServiceInterface::class, RedisLockService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
