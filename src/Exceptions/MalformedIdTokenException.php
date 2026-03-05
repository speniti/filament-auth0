<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

use Throwable;

class MalformedIdTokenException extends Auth0Exception
{
    public function __construct(
        private readonly Throwable $exception
    ) {
        parent::__construct(400, $exception->getMessage());
    }

    public static function create(Throwable $exception): self
    {
        return new self($exception);
    }

    /**
     * @return array{
     *     exception: string,
     *     message: string,
     *     file: string,
     *     line: int
     * }
     */
    public function context(): array
    {
        return [
            'exception' => $this->exception::class,
            'message' => $this->exception->getMessage(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
        ];
    }
}
