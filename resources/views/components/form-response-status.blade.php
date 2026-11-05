@props(['status'])

@php
    $tone = match ($status) {
        'processed' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'spam' => 'border-red-200 bg-red-50 text-red-800',
        'submitted' => 'border-blue-200 bg-blue-50 text-blue-800',
        default => 'border-line bg-canvas text-muted',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium capitalize', $tone]) }}>
    {{ $status }}
</span>
