<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class TrackUserLastLogin
{
    public function handle(Login $event): void
    {
        $event->user->updateQuietly([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }
}
