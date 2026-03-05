@props([
    'href',
    'iconAlias',
    'iconPosition',
    'icon' => 'auth0',
    'label' => __('filament-auth0::login.button.label'),
    'tooltip' => __('filament-auth0::login.button.tooltip'),
])

<hr>

<x-filament::button tag="a" :$href color="gray" :$tooltip :$icon :$iconAlias :$iconPosition>
    {{ $label }}
</x-filament::button>
