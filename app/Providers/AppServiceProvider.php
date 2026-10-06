<?php

namespace App\Providers;

use App\View\Composers\NavigationComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised());

        View::composer('layouts.app', NavigationComposer::class);
    }
}
