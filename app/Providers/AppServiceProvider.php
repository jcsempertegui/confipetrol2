<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (app()->isProduction() && config('app.debug')) {
            throw new RuntimeException('APP_DEBUG debe estar desactivado en producción.');
        }
        if (app()->isProduction() && (! config('session.encrypt') || ! config('session.secure'))) {
            throw new RuntimeException('Las sesiones deben estar cifradas y usar cookies seguras en producción.');
        }

        Password::defaults(fn () => Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols());
    }
}
