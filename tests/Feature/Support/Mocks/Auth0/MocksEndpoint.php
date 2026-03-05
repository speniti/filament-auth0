<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Mocks\Auth0;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;

interface MocksEndpoint
{
    public string $baseUrl {
        get;
    }

    /** @var array<int|string, mixed> */
    public array $body {
        get;
    }

    public string $endpoint {
        get;
    }

    public string $method {
        get;
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     * *
     * * @param  callable|(Closure(Request, ClientResponse|null): bool)|null  $callback  An optional callback to perform additional assertions against the request/response pair.
     */
    public static function assertCalled(callable|Closure|null $callback = null): void;

    /**
     * Assert that no request / response pair was recorded matching a given truth test.
     *
     * @param  callable|(Closure(Request, ClientResponse|null): bool)|null  $callback  An optional callback to perform
     *                                                                                 additional assertions against
     *                                                                                 the request/response pair.
     */
    public static function assertNotCalled(callable|Closure|null $callback = null): void;

    /**
     * Simulate a connection refused response to the endpoint.
     *
     * This will throw a {@see RequestException} when the endpoint is called.
     */
    public static function connectionRefused(): void;

    /**
     * Simulate a failed request to the endpoint.
     *
     * This will throw a {@see RequestException} when the endpoint is called.
     */
    public static function fail(): void;

    /**
     * Simulate a failed request to the endpoint that throws an exception.
     *
     * This will throw a {@see RequestException} when the endpoint is called.
     */
    public static function failWithException(): void;

    /**
     * Fake a response to the endpoint.
     *
     * If the $body is a callable, it will be passed to {@see Http::fake}.
     * Otherwise, it will be merged with the default response body.
     *
     * @param  callable|array<int|string, mixed>|RequestException  $body  The response body. Defaults to an empty array.
     * @param  int  $status  The HTTP status code. Defaults to 200.
     * @param  array<string, mixed>  $headers  The response headers. Defaults to an empty array.
     */
    public static function fake(
        callable|array|RequestException $body = [],
        int $status = 200,
        array $headers = [],
    ): void;
}
