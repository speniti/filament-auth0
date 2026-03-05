<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jwt\IdToken;

use function app;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pipeline\Pipeline as BasePipeline;
use Peniti\FilamentAuth0\Contracts\ProvisionsUser;
use Peniti\FilamentAuth0\Jwt\IdToken;
use Peniti\FilamentAuth0\Jwt\IdToken\Stages\ValidateClaims;

class Pipeline extends BasePipeline
{
    protected $pipes = [
        ValidateClaims::class,
    ];

    public static function process(IdToken $token): Authenticatable
    {
        $pipeline = app(self::class);

        if (app()->has(ProvisionsUser::class)) {
            $pipeline->pipe(app(ProvisionsUser::class));
        }

        return $pipeline->send($token)->thenReturn();
    }
}
