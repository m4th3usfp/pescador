<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        date_default_timezone_set('America/Sao_Paulo');

        Gate::define('view-payment-records', fn ($user) => $user->isAdmin());
        Gate::define('view-activity-logs', fn ($user) => $user->isAdmin());
        Gate::define('switch-city', fn ($user) => $user->canSwitchCity());
        Gate::define('manage-fishermen', fn ($user) => $user->canSwitchCity());
    }
}
