@props(['name', 'label', 'type' => 'text', 'hint' => null])

<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-medium">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @if ($errors->has($name)) aria-describedby="{{ $name }}-error"
        @elseif ($hint) aria-describedby="{{ $name }}-hint" @endif
        {{ $attributes->class(['min-h-14 w-full rounded-[20px] border bg-paper px-5 py-3 text-base placeholder:text-muted/70', 'border-red-700' => $errors->has($name), 'border-line' => ! $errors->has($name)]) }}
    >
    @if ($hint)
        <p id="{{ $name }}-hint" class="text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" role="alert" class="text-sm text-red-800">{{ $message }}</p>
    @enderror
</div>
