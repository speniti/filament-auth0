<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Peniti\FilamentAuth0\Facades\Auth0Tokens;
use Peniti\FilamentAuth0\Jobs\RefreshAuth0Token as RefreshAuth0TokenJob;
use Symfony\Component\HttpFoundation\Response;

readonly class RefreshAuth0Token
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth0Tokens::needsRefresh()) {
            // Use deferred refresh for immediate response, queued job as backup
            defer(static fn () => Auth0Tokens::refresh());

            RefreshAuth0TokenJob::dispatch(Auth::id())->delay(30);
        }

        return $next($request);
    }
}
