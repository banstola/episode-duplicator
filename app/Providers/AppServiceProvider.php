<?php

namespace App\Providers;

use App\Contracts\LockServiceInterface;
use App\Service\RedisLockService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
