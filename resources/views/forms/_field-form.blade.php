@php
    $typeLabels = [
        'short_text' => 'Short text',
        'paragraph' => 'Paragraph',
        'multiple_choice' => 'Multiple choice',
        'checkboxes' => 'Checkboxes',
        'dropdown' => 'Dropdown',
        'file_upload' => 'File upload',
        'date' => 'Date',
        'time' => 'Time',
        'email' => 'Email',
        'number' => 'Number',
        'url' => 'URL',
        'phone' => 'Phone',
        'linear_scale' => 'Linear scale',
    ];
    $options = $field?->options ?? collect();
    $isChoice = in_array($field?->type, $choiceTypes, true);
    $nextOptionIndex = $options->isEmpty() ? 0 : $options->keys()->map(fn ($key): int => (int) $key)->max() + 1;
    $errorPrefix = str_replace(['[', ']'], ['.', ''], $prefix);
    $errorIdPrefix = str_replace(['.', '[', ']'], '-', $prefix);
    $hasError = fn (string $key): bool => $errors->has($errorPrefix.'.'.$key);
    $errorMessage = fn (string $key): ?string => $errors->first($errorPrefix.'.'.$key);
    $inputClasses = function (string $key, string $extra = '') use ($hasError): string {
        return 'min-h-11 w-full rounded-[16px] border bg-paper px-4 py-2 text-sm placeholder:text-muted/70 '
            .($hasError($key) ? 'border-red-700' : 'border-line')
            .($extra !== '' ? ' '.$extra : '');
    };
@endphp

<div data-field @class(['flex flex-col gap-4 rounded-[24px] border bg-paper p-4', 'border-red-700' => $hasError('label') || $hasError('type') || $hasError('field_key'), 'border-line' => ! ($hasError('label') || $hasError('type') || $hasError('field_key'))])>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[11px] font-semibold tracking-widest text-muted uppercase">Question</p>
        <div class="flex items-center gap-1">
            <button type="button" data-move-field-up class="flex size-8 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Move question up">↑</button>
            <button type="button" data-move-field-down class="flex size-8 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Move question down">↓</button>
            <button type="button" data-remove-field class="ml-1 rounded-full px-3 py-1 text-xs text-red-800 transition hover:bg-red-50">Remove</button>
        </div>
    </div>

    @error($errorPrefix.'.id')
        <p role="alert" class="rounded-[12px] bg-red-50 px-3 py-2 text-xs text-red-800">{{ $message }}</p>
    @enderror

    @if ($field?->id)
        <input type="hidden" name="{{ $prefix }}[id]" value="{{ $field->id }}">
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium">Label <span class="text-accent" aria-hidden="true">*</span></label>
            <input name="{{ $prefix }}[label]" value="{{ $field?->label }}" placeholder="e.g. Full name" data-field-label
                aria-invalid="{{ $hasError('label') ? 'true' : 'false' }}"
                @if ($hasError('label')) aria-describedby="{{ $errorIdPrefix }}-label-error" @endif
                class="{{ $inputClasses('label') }}">
            @if ($hasError('label'))
                <p id="{{ $errorIdPrefix }}-label-error" role="alert" class="text-xs text-red-800">{{ $errorMessage('label') }}</p>
            @endif
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium">Type <span class="text-accent" aria-hidden="true">*</span></label>
            <select name="{{ $prefix }}[type]" data-field-type
                aria-invalid="{{ $hasError('type') ? 'true' : 'false' }}"
                @if ($hasError('type')) aria-describedby="{{ $errorIdPrefix }}-type-error" @endif
                class="{{ $inputClasses('type') }}">
                @foreach ($fieldTypes as $type)
                    <option value="{{ $type }}" @selected(($field?->type ?? 'short_text') === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                @endforeach
            </select>
            @if ($hasError('type'))
                <p id="{{ $errorIdPrefix }}-type-error" role="alert" class="text-xs text-red-800">{{ $errorMessage('type') }}</p>
            @endif
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium">Answer key</label>
            <input name="{{ $prefix }}[field_key]" value="{{ $field?->field_key }}" placeholder="Auto-generated from the label"
                aria-invalid="{{ $hasError('field_key') ? 'true' : 'false' }}"
                @if ($hasError('field_key')) aria-describedby="{{ $errorIdPrefix }}-field-key-error" @endif
                class="{{ $inputClasses('field_key') }}">
            @if ($hasError('field_key'))
                <p id="{{ $errorIdPrefix }}-field-key-error" role="alert" class="text-xs text-red-800">{{ $errorMessage('field_key') }}</p>
            @else
                <p class="text-[11px] text-muted">Stable key used in responses and exports. Leave blank to generate one.</p>
            @endif
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium">Maps to</label>
            <select name="{{ $prefix }}[maps_to]"
                aria-invalid="{{ $hasError('maps_to') ? 'true' : 'false' }}"
                @if ($hasError('maps_to')) aria-describedby="{{ $errorIdPrefix }}-maps-to-error" @endif
                class="{{ $inputClasses('maps_to') }}">
                <option value="">No mapping</option>
                @foreach (['candidate.full_name' => 'Candidate full name', 'candidate.email' => 'Candidate email', 'candidate.phone' => 'Candidate phone', 'candidate.linkedin_url' => 'Candidate LinkedIn', 'candidate.portfolio_url' => 'Candidate portfolio'] as $value => $label)
                    <option value="{{ $value }}" @selected($field?->maps_to === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($hasError('maps_to'))
                <p id="{{ $errorIdPrefix }}-maps-to-error" role="alert" class="text-xs text-red-800">{{ $errorMessage('maps_to') }}</p>
            @endif
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium">Placeholder</label>
            <input name="{{ $prefix }}[placeholder]" value="{{ $field?->placeholder }}"
                aria-invalid="{{ $hasError('placeholder') ? 'true' : 'false' }}"
                @if ($hasError('placeholder')) aria-describedby="{{ $errorIdPrefix }}-placeholder-error" @endif
                class="{{ $inputClasses('placeholder') }}">
            @if ($hasError('placeholder'))
                <p id="{{ $errorIdPrefix }}-placeholder-error" role="alert" class="text-xs text-red-800">{{ $errorMessage('placeholder') }}</p>
            @endif
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium">Help text</label>
            <input name="{{ $prefix }}[help_text]" value="{{ $field?->help_text }}"
                aria-invalid="{{ $hasError('help_text') ? 'true' : 'false' }}"
                @if ($hasError('help_text')) aria-describedby="{{ $errorIdPrefix }}-help-text-error" @endif
                class="{{ $inputClasses('help_text') }}">
            @if ($hasError('help_text'))
                <p id="{{ $errorIdPrefix }}-help-text-error" role="alert" class="text-xs text-red-800">{{ $errorMessage('help_text') }}</p>
            @endif
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="{{ $prefix }}[is_required]" value="1" @checked($field?->is_required)>
        Required question
    </label>

    <div data-options-editor @class(['flex flex-col gap-2', 'hidden' => ! $isChoice])>
        <p class="text-xs font-medium">Options</p>
        <div data-options data-next-index="{{ $nextOptionIndex }}" class="flex flex-col gap-2">
            @foreach ($options as $optionIndex => $option)
                @php
                    $optionLabelKey = "options.$optionIndex.label";
                    $optionValueKey = "options.$optionIndex.value";
                @endphp
                <div data-option class="flex flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <input name="{{ $prefix }}[options][{{ $optionIndex }}][label]" value="{{ $option->label }}" placeholder="Label"
                            aria-invalid="{{ $hasError($optionLabelKey) ? 'true' : 'false' }}"
                            @if ($hasError($optionLabelKey)) aria-describedby="{{ $errorIdPrefix }}-option-{{ $optionIndex }}-label-error" @endif
                            class="{{ $inputClasses($optionLabelKey, 'flex-1') }}">
                        <input name="{{ $prefix }}[options][{{ $optionIndex }}][value]" value="{{ $option->value }}" placeholder="Stored value (optional)"
                            aria-invalid="{{ $hasError($optionValueKey) ? 'true' : 'false' }}"
                            @if ($hasError($optionValueKey)) aria-describedby="{{ $errorIdPrefix }}-option-{{ $optionIndex }}-value-error" @endif
                            class="{{ $inputClasses($optionValueKey, 'flex-1') }}">
                        <button type="button" data-remove-option class="flex size-8 shrink-0 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Remove option">×</button>
                    </div>
                    @if ($hasError($optionLabelKey))
                        <p id="{{ $errorIdPrefix }}-option-{{ $optionIndex }}-label-error" role="alert" class="text-xs text-red-800">{{ $errorMessage($optionLabelKey) }}</p>
                    @endif
                    @if ($hasError($optionValueKey))
                        <p id="{{ $errorIdPrefix }}-option-{{ $optionIndex }}-value-error" role="alert" class="text-xs text-red-800">{{ $errorMessage($optionValueKey) }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" data-add-option class="inline-flex w-fit min-h-9 items-center rounded-full border border-line px-3 py-1 text-xs transition hover:bg-canvas">Add option</button>
        <p class="text-[11px] text-muted">Options apply to multiple choice, checkboxes, and dropdown fields.</p>
    </div>
</div>
