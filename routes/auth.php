<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Peniti\FilamentAuth0\Auth0Plugin;
use Peniti\FilamentAuth0\Http\Controllers\AuthorizationController;
use Peniti\FilamentAuth0\Http\Controllers\CallbackController;
use Peniti\FilamentAuth0\RoutePath;

Route::name('filament.')
    ->group(function () {
        foreach (Filament::getPanels() as $panel) {
            if (! $panel->hasPlugin(Auth0Plugin::get()->getId())) {
                continue;
            }

            $domains = empty($domains = $panel->getDomains()) ? [''] : $domains;

            foreach ($domains as $domain) {
                Route::domain($domain)
                    ->middleware($panel->getMiddleware())
                    ->name(sprintf('%s%s.auth0.', $panel->getId(), filled($domain) && count($domains) > 1 ? ".$domain" : ''))
                    ->prefix($panel->getPath().'/auth0')
                    ->group(function () {
                        Route::get(RoutePath::for('authorize', 'authorize'), AuthorizationController::class)
                            ->middleware('throttle:10,1')
                            ->name('authorize');

                        Route::get(RoutePath::for('callback', 'callback'), CallbackController::class)
                            ->name('callback');
                    });
            }
        }
    });
