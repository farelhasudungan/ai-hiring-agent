@php
    $isEdit = isset($form);
@endphp

<form action="{{ $isEdit ? route('forms.update', $form) : route('forms.store') }}" method="POST" class="flex flex-col gap-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-form-input name="title" label="Form title" placeholder="e.g. Senior Laravel Developer Application" maxlength="255" required autofocus :value="$isEdit ? $form->title : null" />

    <div class="flex flex-col gap-2">
        <label for="description" class="flex items-center gap-2 text-sm font-medium">
            Description
            <span class="text-xs font-normal text-muted">Optional</span>
        </label>
        <textarea id="description" name="description" rows="3" class="w-full rounded-[20px] border border-line bg-paper px-5 py-4 text-base leading-relaxed placeholder:text-muted/70">{{ old('description', $isEdit ? $form->description : '') }}</textarea>
        @error('description')
            <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="flex flex-col gap-2">
            <label for="status" class="flex items-center gap-2 text-sm font-medium">
                Status
                <span class="text-accent" aria-hidden="true">*</span>
            </label>
            <select id="status" name="status" required class="min-h-14 w-full rounded-[20px] border border-line bg-paper px-5 py-3 text-base">
                @foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $isEdit ? $form->status : 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-2">
            <label for="closes_at" class="flex items-center gap-2 text-sm font-medium">
                Closes at
                <span class="text-xs font-normal text-muted">Optional</span>
            </label>
            <input id="closes_at" name="closes_at" type="datetime-local" value="{{ old('closes_at', $isEdit && $form->closes_at ? $form->closes_at->format('Y-m-d\TH:i') : '') }}" class="min-h-14 w-full rounded-[20px] border border-line bg-paper px-5 py-3 text-base">
            @error('closes_at')
                <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col gap-2">
        <label for="confirmation_message" class="flex items-center gap-2 text-sm font-medium">
            Confirmation message
            <span class="text-xs font-normal text-muted">Optional</span>
        </label>
        <textarea id="confirmation_message" name="confirmation_message" rows="3" maxlength="1000" placeholder="Thank you for applying. We will review your submission shortly." class="w-full rounded-[20px] border border-line bg-paper px-5 py-4 text-base leading-relaxed placeholder:text-muted/70">{{ old('confirmation_message', $isEdit ? ($form->settings['confirmation_message'] ?? '') : '') }}</textarea>
        <p class="text-xs text-muted">Shown to candidates after they submit this form.</p>
        @error('confirmation_message')
            <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line/60 pt-6">
        <a href="{{ $isEdit ? route('forms.show', $form) : route('forms.index') }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">Cancel</a>
        <x-primary-button>{{ $isEdit ? 'Save changes' : 'Create form' }}</x-primary-button>
    </div>
</form>
