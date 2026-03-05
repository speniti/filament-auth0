<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Peniti\FilamentAuth0\Events\Auth0AuthenticationFailed;
use Peniti\FilamentAuth0\Facades\Auth0Tokens;
use Throwable;

class RefreshAuth0Token implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $backoff = 60;

    public int $tries = 3;

    public function __construct(
        private readonly int|string $userId,
        #[Config('filament-auth0.queues.token_refresh')] public ?string $queue = null,
    ) {}

    public function failed(Throwable $exception): void
    {
        Auth0Tokens::forget($this->userId);

        event(new Auth0AuthenticationFailed($exception));
    }

    public function handle(): void
    {
        if (! Auth0Tokens::needsRefresh()) {
            return;
        }

        Auth0Tokens::refresh();
    }
}
