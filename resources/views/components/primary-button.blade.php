<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-14 items-center justify-center gap-4 rounded-[20px] bg-ink px-6 py-3 font-medium text-canvas transition hover:bg-ink/85 active:scale-[0.99]']) }}>
    {{ $slot }}
    <span aria-hidden="true">↗</span>
</button>
