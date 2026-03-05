<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0;

use function data_get;

use Filament\Support\Facades\FilamentIcon;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ComponentAttributeBag;
use Peniti\FilamentAuth0\Actions\ValidateMetadata;
use Peniti\FilamentAuth0\Auth\Auth0TokenManager;
use Peniti\FilamentAuth0\Auth\Auth0UserProvider;
use Peniti\FilamentAuth0\Commands\Auth0Healthcheck;
use Peniti\FilamentAuth0\Commands\ClearAuth0Tokens;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;
use Peniti\FilamentAuth0\Http\Middleware\RefreshAuth0Token;
use Peniti\FilamentAuth0\Jwks\CachedKeySet;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;

use function view;

class Auth0ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-auth0.php', 'filament-auth0');
        $this->publishes([
            __DIR__.'/../config/filament-auth0.php' => config_path('filament-auth0.php'),
        ], 'filament-auth0-config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-auth0');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-auth0'),
        ], 'filament-auth0-views');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'filament-auth0');
        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/filament-auth0'),
        ], 'filament-auth0-translations');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'filament-auth0-migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/auth.php');

        Auth::provider('auth0', static fn (Application $app) => $app->make(Auth0UserProvider::class));

        FilamentIcon::register([
            IconAlias::AUTH0_ICON => view('filament-auth0::components.auth0-icon', [
                'attributes' => new ComponentAttributeBag(),
            ]),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearAuth0Tokens::class,
                Auth0Healthcheck::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->singleton(Auth0TokenManager::class);

        $this->app->bind(Auth0TokenStore::class, function (Application $app): Auth0TokenStore {
            return $app->make(Auth0TokenManager::class)->store();
        });

        $this->app->singleton(CachedMetadata::class, function (Application $app): CachedMetadata {
            return new CachedMetadata(
                domain: Config::string('filament-auth0.domain'),
                ttl: Config::string('filament-auth0.metadata.ttl'),
                timeout: Config::integer('filament-auth0.http.timeout'),
                retryTimes: Config::integer('filament-auth0.http.retry_times'),
                retrySleep: Config::integer('filament-auth0.http.retry_sleep'),
                verifier: $app->make(ValidateMetadata::class)
            );
        });

        $this->app->singleton(CachedKeySet::class, function (Application $app): CachedKeySet {
            $metadata = $app->make(CachedMetadata::class);

            /** @var string $jwksUri */
            $jwksUri = data_get($metadata, 'jwks_uri');

            return new CachedKeySet(
                $jwksUri,
                Config::string('filament-auth0.keys.ttl'),
                Config::integer('filament-auth0.http.timeout'),
                Config::integer('filament-auth0.http.retry_times'),
                Config::integer('filament-auth0.http.retry_sleep')
            );
        });

        $this->app->singleton(RefreshAuth0Token::class);
    }
}
