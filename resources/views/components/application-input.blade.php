@props(['name', 'label', 'type' => 'text', 'required' => false])

@php
    $value = old($name);
    $value = is_string($value) || is_numeric($value) ? $value : '';
@endphp

<div>
    <label for="application-{{ $name }}" class="mb-2 block text-sm font-medium">{{ $label }} @if ($required)<span aria-hidden="true">*</span>@endif</label>
    <input id="application-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" @required($required)
        {{ $attributes->class(['auth-input', 'auth-input-invalid' => $errors->has($name)]) }}
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @error($name) aria-describedby="application-{{ $name }}-error" @enderror>
    @error($name)<p id="application-{{ $name }}-error" class="mt-2 text-sm text-body" role="alert">{{ $message }}</p>@enderror
</div>
