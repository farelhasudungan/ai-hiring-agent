@props(['status'])

@php
    $isPublished = $status === 'published';
    $isClosed = $status === 'closed';
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium capitalize',
    'border-emerald-200 bg-emerald-50 text-emerald-800' => $isPublished,
    'border-red-200 bg-red-50 text-red-800' => $isClosed,
    'border-line bg-canvas text-muted' => ! $isPublished && ! $isClosed,
]) }}>
    <span @class([
        'size-1.5 rounded-full',
        'bg-emerald-600' => $isPublished,
        'bg-red-600' => $isClosed,
        'bg-muted' => ! $isPublished && ! $isClosed,
    ]) aria-hidden="true"></span>
    {{ $status }}
</span>
