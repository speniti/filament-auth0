<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0;

use Illuminate\Support\Facades\Config;

class RoutePath
{
    public static function for(string $route, string $default): string
    {
        return Config::string("filament-auth0.paths.$route", $default);
    }
}
