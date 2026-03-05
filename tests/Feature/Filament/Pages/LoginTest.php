<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Panel;
use Peniti\FilamentAuth0\Auth0Plugin;
use Peniti\FilamentAuth0\LoginBehavior;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;

use function Pest\Laravel\get;

use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;

describe('Login page behavior', function () {
    test('unauthenticated users are redirected to filament login page by default', function () {
        DiscoveryEndpoint::fake();

        /** @var Panel $panel */
        $panel = Filament::getPanel('admin');

        /** @var Auth0Plugin $plugin */
        $plugin = $panel->getPlugin(Auth0Plugin::get()->getId());
        expect($plugin->getLoginBehavior())->toBe(LoginBehavior::DEFAULT);

        get($panel->getUrl())->assertRedirect($panel->getLoginUrl());
        get($panel->getLoginUrl())->assertSeeLivewire(Login::class);
    });

    test('unauthenticated users are redirected to auth0 login page if login behavior is set to redirect', function () {
        DiscoveryEndpoint::fake();

        /** @var Panel $panel */
        $panel = Filament::getPanel('redirect');

        /** @var Auth0Plugin $plugin */
        $plugin = $panel->getPlugin(Auth0Plugin::get()->getId());
        expect($plugin->getLoginBehavior())->toBe(LoginBehavior::REDIRECT);

        /** @var string $endpoint */
        $endpoint = data_get(app(CachedMetadata::class), 'authorization_endpoint');

        get($panel->getUrl())->assertRedirect($panel->getLoginUrl());
        get($panel->getLoginUrl())->assertRedirectContains($endpoint);
    });
});
