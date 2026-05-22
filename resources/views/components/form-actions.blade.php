@props([
    'cancelUrl',
    'submitLabel' => 'Save',
    'submitIcon' => 'check-lg',
])

<div {{ $attributes->merge(['class' => 'form-actions toolbar-icon-group mt-3']) }}>
    <x-icon-btn type="submit" :icon="$submitIcon" :label="$submitLabel" variant="primary" :iconOnly="false" />
    <x-icon-btn :href="$cancelUrl" icon="x-lg" label="Cancel" variant="outline-secondary" :iconOnly="false" />
    {{ $slot }}
</div>
