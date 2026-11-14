<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand px-6 py-2.5 text-sm font-medium text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)] transition hover:bg-brand/90 active:scale-[0.99]']) }}>
    {{ $slot }}
    <img src="{{ asset('images/landing/arrow-right-white.svg') }}" alt="" width="16" height="16" class="size-4">
</button>
