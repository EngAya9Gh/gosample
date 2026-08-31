@php
    $map = [
        'NEW'         => ['bg-primary',   'NEW'],
        'COLLECTED'   => ['bg-info',      'COLLECTED'],
        'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
        'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
        'CLOSED'      => ['bg-success',   'CLOSED'],
        'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
    ];
@endphp
@if(isset($map[$status]))
    <span class="badge {{ $map[$status][0] }}">{{ $map[$status][1] }}</span>
@else
    {{ $status ?? '' }}
@endif
