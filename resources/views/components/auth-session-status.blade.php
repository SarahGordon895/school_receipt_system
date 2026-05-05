@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-800 bg-green-50 border border-green-200/80 rounded-lg px-3 py-2']) }}>
        {{ $status }}
    </div>
@endif
