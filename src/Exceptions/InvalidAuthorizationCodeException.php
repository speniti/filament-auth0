<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

use Illuminate\Http\Client\Response;

class InvalidAuthorizationCodeException extends Auth0Exception
{
    public function __construct(
        private readonly Response $response,
    ) {
        parent::__construct(400, "Authorization code rejected: {$this->response->body()}");
    }

    public static function create(Response $response): self
    {
        return new self($response);
    }

    /**
     * @return array{
     *     response_status: int,
     *     response_body: string
     * }
     */
    public function context(): array
    {
        return [
            'response_status' => $this->response->status(),
            'response_body' => $this->response->body(),
        ];
    }
}
