<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\Feature\Support\Providers\Filament\PlainPanelProvider;
use Tests\Feature\Support\Providers\Filament\RedirectPanelProvider;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;
    use WithWorkbench;

    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent accidental HTTP requests to real endpoints
        // This will throw an exception if a test makes an HTTP request
        // that hasn't been explicitly faked
        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),

            // Panel providers
            RedirectPanelProvider::class,
            PlainPanelProvider::class,
        ];
    }
}
