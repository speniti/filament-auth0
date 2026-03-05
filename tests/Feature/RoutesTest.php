<?php

/** @noinspection LaravelUnknownRouteNameInspection */
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Filament\Facades\Filament;
use Peniti\FilamentAuth0\Auth0Plugin;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

describe('Route registration', function () {
    test('the callback route is registered', function () {
        foreach (Filament::getPanels() as $panel) {
            $id = $panel->getId();
            $domains = empty($domains = $panel->getDomains()) ? [''] : $domains;

            // Ensure that if the plugin is not configured for the panel, the corresponding route is not registered.
            if (! $panel->hasPlugin(Auth0Plugin::get()->getId())) {
                expect(static fn () => route("filament.$id.auth0.callback"))
                    ->toThrow(RouteNotFoundException::class);

                foreach ($domains as $domain) {
                    expect(static fn () => route("filament.$id.$domain.auth0.callback"))
                        ->toThrow(RouteNotFoundException::class);
                }
            }

            // Ensure that if the panel has multiple domains, a route is registered for each domain.
            if (count($domains) > 1) {
                foreach ($domains as $domain) {
                    expect(route("filament.$id.$domain.auth0.callback"))->toBeString();
                }

                return;
            }

            // Ensure that if the panel has a single domain, a route is registered without the domain suffix.
            expect(route("filament.$id.auth0.callback"))->toBeString();
        }
    });
});
