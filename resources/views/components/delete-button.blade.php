@can('can-delete')
<button {{ $attributes->merge(['class' => 'btn btn-danger']) }}>
    {{ $slot ?: 'Delete' }}
</button>
@endcan
