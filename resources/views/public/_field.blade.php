@php
    $errorKey = 'answers.'.$field->id;
    $hasError = $errors->has($errorKey) || $errors->has($errorKey.'.*');
    $errorMessage = $errors->first($errorKey) ?: $errors->first($errorKey.'.*');
    $oldValue = old($errorKey);
    $checkedValues = is_array($oldValue) ? $oldValue : [];
    $validation = $field->validation ?? [];
    $describedBy = collect([
        $field->help_text ? 'field-'.$field->id.'-help' : null,
        $hasError ? 'field-'.$field->id.'-error' : null,
    ])->filter()->implode(' ');
    $inputClasses = 'min-h-12 w-full rounded-[16px] border bg-paper px-4 py-3 text-sm placeholder:text-muted/70 '
        .($hasError ? 'border-red-700' : 'border-line');
    $inputType = match ($field->type) {
        'email' => 'email',
        'url' => 'url',
        'phone' => 'tel',
        'number' => 'number',
        'date' => 'date',
        'time' => 'time',
        default => 'text',
    };
    $accept = is_array($validation['mime_types'] ?? null)
        ? implode(',', array_filter($validation['mime_types'], 'is_string'))
        : '';
    $isGroup = in_array($field->type, ['multiple_choice', 'checkboxes', 'linear_scale'], true);
@endphp

<div @class(['flex flex-col gap-2', 'rounded-[20px]' => $hasError]) @if ($hasError) data-field-error @endif>
    @if ($isGroup)
        <p id="field-{{ $field->id }}" class="flex flex-wrap items-center gap-2 text-sm font-medium">
            <span>{{ $field->label }}</span>
            @if ($field->is_required)
                <span class="text-accent" aria-hidden="true">*</span>
            @else
                <span class="text-xs font-normal text-muted">Optional</span>
            @endif
        </p>
    @else
        <label for="field-{{ $field->id }}" class="flex flex-wrap items-center gap-2 text-sm font-medium">
            <span>{{ $field->label }}</span>
            @if ($field->is_required)
                <span class="text-accent" aria-hidden="true">*</span>
            @else
                <span class="text-xs font-normal text-muted">Optional</span>
            @endif
        </label>
    @endif

    @if ($field->help_text)
        <p id="field-{{ $field->id }}-help" class="text-xs text-muted">{{ $field->help_text }}</p>
    @endif

    @if ($field->type === 'paragraph')
        <textarea id="field-{{ $field->id }}" name="answers[{{ $field->id }}]" rows="5"
            placeholder="{{ $field->placeholder }}"
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            class="{{ $inputClasses }}">{{ is_scalar($oldValue) ? $oldValue : '' }}</textarea>
    @elseif ($field->type === 'multiple_choice')
        <div role="radiogroup" aria-labelledby="field-{{ $field->id }}" class="flex flex-col gap-2">
            @forelse ($field->options as $option)
                <label class="flex cursor-pointer items-start gap-3 rounded-[16px] border {{ $hasError ? 'border-red-200' : 'border-line' }} bg-canvas/40 p-3 text-sm transition hover:bg-canvas">
                    <input type="radio" name="answers[{{ $field->id }}]" value="{{ $option->value }}"
                        @checked((string) $oldValue === (string) $option->value)
                        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                        class="mt-1">
                    <span>{{ $option->label }}</span>
                </label>
            @empty
                <p class="text-sm text-muted">No options have been added to this question yet.</p>
            @endforelse
        </div>
    @elseif ($field->type === 'checkboxes')
        <div role="group" aria-labelledby="field-{{ $field->id }}" class="flex flex-col gap-2">
            @forelse ($field->options as $option)
                <label class="flex cursor-pointer items-start gap-3 rounded-[16px] border {{ $hasError ? 'border-red-200' : 'border-line' }} bg-canvas/40 p-3 text-sm transition hover:bg-canvas">
                    <input type="checkbox" name="answers[{{ $field->id }}][]" value="{{ $option->value }}"
                        @checked(in_array($option->value, $checkedValues, true))
                        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                        class="mt-1">
                    <span>{{ $option->label }}</span>
                </label>
            @empty
                <p class="text-sm text-muted">No options have been added to this question yet.</p>
            @endforelse
        </div>
    @elseif ($field->type === 'dropdown')
        <select id="field-{{ $field->id }}" name="answers[{{ $field->id }}]"
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            class="{{ $inputClasses }}">
            <option value="">Select an option</option>
            @foreach ($field->options as $option)
                <option value="{{ $option->value }}" @selected((string) $oldValue === (string) $option->value)>{{ $option->label }}</option>
            @endforeach
        </select>
    @elseif ($field->type === 'linear_scale')
        <div class="flex flex-col gap-2">
            <div role="radiogroup" aria-labelledby="field-{{ $field->id }}" class="flex flex-wrap gap-2">
                @foreach (range(1, 5) as $scale)
                    <label class="flex min-w-12 cursor-pointer items-center justify-center rounded-[16px] border {{ $hasError ? 'border-red-200' : 'border-line' }} bg-canvas/40 px-3 py-2 text-sm transition hover:bg-canvas">
                        <input type="radio" name="answers[{{ $field->id }}]" value="{{ $scale }}"
                            @checked((string) $oldValue === (string) $scale)
                            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                            class="sr-only">
                        <span>{{ $scale }}</span>
                    </label>
                @endforeach
            </div>
            <div class="flex justify-between text-[11px] text-muted">
                <span>Low</span>
                <span>High</span>
            </div>
        </div>
    @elseif ($field->type === 'file_upload')
        <input id="field-{{ $field->id }}" type="file" name="answers[{{ $field->id }}]"
            @if ($accept !== '') accept="{{ $accept }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            class="block w-full cursor-pointer rounded-[16px] border border-dashed bg-paper p-3 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-medium file:text-canvas hover:bg-canvas/40 {{ $hasError ? 'border-red-700' : 'border-line' }}">
    @else
        <input id="field-{{ $field->id }}" type="{{ $inputType }}" name="answers[{{ $field->id }}]"
            value="{{ is_scalar($oldValue) ? $oldValue : '' }}"
            @if ($field->placeholder) placeholder="{{ $field->placeholder }}" @endif
            @if ($field->type === 'number') step="any" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            class="{{ $inputClasses }}">
    @endif

    @if ($hasError && $errorMessage)
        <p id="field-{{ $field->id }}-error" role="alert" class="text-xs text-red-800">{{ $errorMessage }}</p>
    @endif
</div>
