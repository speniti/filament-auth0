<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Commands;

use Illuminate\Console\Command;
use Peniti\FilamentAuth0\Facades\Auth0Tokens;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'auth0:clear-tokens')]
class ClearAuth0Tokens extends Command
{
    protected $description = 'Clear stored Auth0 tokens for a user or all users.';

    protected $signature = 'auth0:clear-tokens
                            {user : The user ID.}
                            {--force : Force without confirmation.}';

    public function handle(): int
    {
        $userId = $this->argument('user');

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to clear Auth0 tokens for this user?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        Auth0Tokens::forget($userId);

        $this->info("Tokens cleared for user: $userId");

        return self::SUCCESS;
    }
}
