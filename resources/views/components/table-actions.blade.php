@props([
    'edit' => null,
    'view' => null,
    'delete' => null,
    'deleteConfirm' => 'Are you sure you want to delete this record?',
])

<div class="table-actions" role="group" aria-label="Row actions">
    @if ($view)
        <x-icon-btn :href="$view" icon="eye" label="View details" variant="outline-secondary" size="sm" />
    @endif
    @if ($edit)
        <x-icon-btn :href="$edit" icon="pencil-square" label="Edit" variant="outline-primary" size="sm" />
    @endif
    @if ($delete)
        <form action="{{ $delete }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <x-icon-btn type="submit" icon="trash" label="Delete" variant="outline-danger" size="sm"
                :confirm="$deleteConfirm" />
        </form>
    @endif
    {{ $slot }}
</div>
