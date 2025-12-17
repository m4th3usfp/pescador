<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogUserLogin
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
    public function handle(Login $event): void
    {
        activity('Entrou')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('Login')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Usuário {$event->user->name} entrou");
    }
}
