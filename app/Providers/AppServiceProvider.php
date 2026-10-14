<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (App::isProduction()) {
            URL::forceScheme('https');
        }

        // Register authorization policies
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // RA-08: Enforce strong password defaults across authentication and profile actions
        Password::defaults(function () {
            $rule = Password::min(8);
            return app()->environment(['production', 'staging'])
                ? $rule->letters()->mixedCase()->numbers()->symbols()
                : $rule;
        });
    }
}
