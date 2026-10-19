@props(['field', 'label', 'type' => 'text', 'hint' => null])
<div>
    <label for="auth-{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
    <input id="auth-{{ $field }}" type="{{ $type }}" wire:model="data.{{ $field }}"
        {{ $attributes->class(['auth-input', 'auth-input-invalid' => $errors->has('data.'.$field)]) }}
        aria-invalid="{{ $errors->has('data.'.$field) ? 'true' : 'false' }}"
        aria-describedby="auth-{{ $field }}-help">
    <div id="auth-{{ $field }}-help" class="mt-2 text-sm leading-relaxed" aria-live="polite">
        @error('data.'.$field)
            <p class="text-ink">{{ $message }}</p>
        @else
            @if ($hint)<p class="text-muted">{{ $hint }}</p>@endif
        @enderror
    </div>
</div>
