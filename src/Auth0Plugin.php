<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0;

use function action;
use function filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Enums\IconPosition;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Peniti\FilamentAuth0\Http\Controllers\AuthorizationController;

class Auth0Plugin implements Plugin
{
    protected LoginBehavior $loginBehavior = LoginBehavior::DEFAULT;

    protected ?string $loginButtonLabel = null;

    protected ?string $loginButtonTooltip = null;

    protected bool $showLoginButton = true;

    public static function get(): static
    {
        /** @var $this */
        return filament(static::make()->getId());
    }

    public static function make(): static
    {
        /** @var $this */
        return app(static::class);
    }

    public function boot(Panel $panel): void
    {
        // Reserved for future use
    }

    public function getId(): string
    {
        return 'speniti/filament-auth0';
    }

    public function getLoginBehavior(): LoginBehavior
    {
        return $this->loginBehavior;
    }

    public function loginBehavior(LoginBehavior $behavior): static
    {
        $this->loginBehavior = $behavior;

        return $this;
    }

    public function loginButtonLabel(string $label): static
    {
        $this->loginButtonLabel = $label;

        return $this;
    }

    public function loginButtonTooltip(string $label): static
    {
        $this->loginButtonTooltip = $label;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if ($this->loginBehavior->shouldRedirect()) {
            $panel->login(AuthorizationController::class);

            return;
        }

        if ($this->showLoginButton) {
            $panel->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => Blade::render('<x-filament-auth0::sign-in-button :$label :$href :$iconAlias :$iconPosition />', [
                    'label' => $this->loginButtonLabel ?? __('filament-auth0::login.button.label'),
                    'tooltip' => $this->loginButtonTooltip ?? __('filament-auth0::login.button.tooltip'),
                    'href' => action(AuthorizationController::class),
                    'iconAlias' => IconAlias::AUTH0_ICON,
                    'iconPosition' => IconPosition::After,
                ]),
            );
        }
    }

    public function showLoginButton(bool $show): static
    {
        $this->showLoginButton = $show;

        return $this;
    }
}
