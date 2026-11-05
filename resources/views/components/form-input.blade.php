@props(['name', 'label', 'type' => 'text', 'hint' => null, 'value' => null])

@php($isRequired = $attributes->has('required'))

<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="flex items-center gap-2 text-sm font-medium">
        {{ $label }}
        @if ($isRequired)
            <span class="text-accent" aria-hidden="true">*</span>
        @else
            <span class="text-xs font-normal text-muted">Optional</span>
        @endif
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
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
