<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Route::group([ 'middleware' => 'cors'], function () {
            Passport::routes();
        });
        // Route::group(['middleware' => ['custom_auth', 'decrypt_pin:password,username']], function () {
            Passport::tokensCan([
                'be' => 'Manage admin panel scope',
                'pos' => 'Manage pos order scope',
                'doctor' => 'Manage doctor scope',
                'landing-page' => 'Manage landing page scope',
            ]);
        // });

        Passport::tokensExpireIn(now()->addDays(15000));
    }
}
