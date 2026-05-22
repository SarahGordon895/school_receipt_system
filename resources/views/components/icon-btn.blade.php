@props([
    'icon',
    'label' => '',
    'variant' => 'outline-secondary',
    'size' => '',
    'href' => null,
    'type' => 'button',
    'iconOnly' => true,
    'confirm' => null,
])

@php
    $iconClass = str_starts_with($icon, 'bi-') ? $icon : 'bi-' . $icon;
    $sizeClass = $size ? " btn-{$size}" : '';
    $classes = trim("btn btn-icon btn-{$variant}{$sizeClass} " . ($attributes->get('class') ?? ''));
    $aria = $label ?: str_replace(['bi-', '-'], ['', ' '], $iconClass);
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $classes }}" title="{{ $label }}" aria-label="{{ $aria }}"
        {{ $attributes->except('class') }}>
        <i class="bi {{ $iconClass }}" aria-hidden="true"></i>
        @unless($iconOnly)
            <span class="btn-icon-text">{{ $label }}</span>
        @endunless
    </a>
@else
    <button type="{{ $type }}" class="{{ $classes }}" title="{{ $label }}" aria-label="{{ $aria }}"
        @if ($confirm) onclick="return confirm(@js($confirm))" @endif
        {{ $attributes->except('class') }}>
        <i class="bi {{ $iconClass }}" aria-hidden="true"></i>
        @unless($iconOnly)
            <span class="btn-icon-text">{{ $label }}</span>
        @endunless
    </button>
@endif
