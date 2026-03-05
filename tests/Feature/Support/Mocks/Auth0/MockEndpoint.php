<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Mocks\Auth0;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function is_array;
use function is_null;

use Symfony\Component\HttpFoundation\Response;

trait MockEndpoint
{
    public string $baseUrl {
        get {
            $domain = Config::string('filament-auth0.domain', 'tenant-id.auth0.com');

            return "https://$domain";
        }
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     *
     * @param  callable|(Closure(Request, ClientResponse|null): bool)|null  $callback  An optional callback to perform
     *                                                                                 additional assertions against
     *                                                                                 the request/response pair.
     */
    public static function assertCalled(callable|Closure|null $callback = null): void
    {
        Http::assertSent(static function (Request $request, ClientResponse $response) use ($callback) {
            $mock = new static;

            if (! Str::is($request->method(), $mock->method, ignoreCase: true)) {
                return false;
            }

            if (! Str::is($request->url(), $mock->endpoint)) {
                return false;
            }

            return is_null($callback) ? true : $callback($request, $response);
        });
    }

    /**
     * Assert that no request / response pair was recorded matching a given truth test.
     *
     * @param  callable|(Closure(Request, ClientResponse|null): bool)|null  $callback  An optional callback to perform
     *                                                                                 additional assertions against
     *                                                                                 the request/response pair.
     */
    public static function assertNotCalled(callable|Closure|null $callback = null): void
    {
        Http::assertNotSent(static function (Request $request, ClientResponse $response) use ($callback) {
            $mock = new static;

            if (! Str::is($request->method(), $mock->method, ignoreCase: true)) {
                return false;
            }

            if (! Str::is($request->url(), $mock->endpoint)) {
                return false;
            }

            return is_null($callback) ? true : $callback($request, $response);
        });
    }

    /**
     * Simulate a connection refused response to the endpoint.
     *
     * This will throw a {@see RequestException} when the endpoint is called.
     */
    public static function connectionRefused(): void
    {
        static::fake(Http::failedConnection());
    }

    /**
     * Simulate a failed request.
     *
     * @param  callable|array<int|string, mixed>|RequestException  $body  The response body. Defaults to an empty array.
     * @param  int  $status  The HTTP status code. Defaults to not
     *                       found (404).
     * @param  array<string, mixed>  $headers  The response headers. Defaults to an empty
     *                                         array.
     */
    public static function fail(array $body = [], int $status = Response::HTTP_NOT_FOUND, array $headers = []): void
    {
        static::fake($body, $status, $headers);
    }

    /**
     * Simulate a failed request by returning a request exception.
     *
     * @param  callable|array<int|string, mixed>|RequestException  $body  The response body. Defaults to null.
     * @param  int  $status  The HTTP status code. Defaults to not
     *                       found (404).
     * @param  array<string, mixed>  $headers  The response headers. Defaults to an empty
     *                                         array.
     */
    public static function failWithException(?array $body = null, int $status = Response::HTTP_NOT_FOUND, array $headers = []): void
    {
        static::fake(Http::failedRequest($body, $status, $headers));
    }

    /**
     * Fake a response to the endpoint.
     *
     * If the $body is a callable, it will be passed to {@see Http::fake}.
     * Otherwise, it will be merged with the default response body.
     *
     * @param  callable|array|RequestException  $body  The response body. Defaults to an empty array.
     * @param  int  $status  The HTTP status code. Defaults to 200.
     * @param  array<string, mixed>  $headers  The response headers. Defaults to an empty array.
     */
    public static function fake(
        callable|array|RequestException $body = [],
        int $status = 200,
        array $headers = [],
    ): void {
        $mock = new static;

        if (! is_array($body)) {
            Http::fake([$mock->endpoint => $body]);

            return;
        }

        Http::fake([
            $mock->endpoint => Http::response(
                body: [...$mock->body, ...$body],
                status: $status,
                headers: ['Content-Type' => 'application/json', ...$headers],
            ),
        ]);
    }
}
