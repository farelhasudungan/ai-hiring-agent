@php
    $toasts = collect([
        ['type' => 'success', 'message' => session('status')],
        ['type' => 'success', 'message' => session('connection_status')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'error', 'message' => $errors->any() ? 'Please check the highlighted fields and try again.' : null],
    ])->filter(fn (array $toast): bool => filled($toast['message']))->values();
@endphp

<div id="toast-region" data-toasts="{{ $toasts->toJson() }}" class="pointer-events-none fixed inset-x-4 top-4 z-50 flex flex-col items-end gap-3 sm:left-auto sm:w-96" aria-live="polite"></div>
