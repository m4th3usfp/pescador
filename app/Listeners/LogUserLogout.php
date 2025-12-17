<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        activity('Saiu')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('Logout')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Usuário {$event->user->name} saiu");
    }
}
