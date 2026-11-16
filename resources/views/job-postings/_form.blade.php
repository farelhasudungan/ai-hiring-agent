@props(['jobPosting' => null])

@php
    $skills = fn (string $field): ?string => old($field, $jobPosting ? implode(', ', $jobPosting->{$field} ?? []) : null);
    $textareaClass = fn (string $field): string => 'w-full rounded-xl border bg-paper px-5 py-3 text-base placeholder:text-muted/70 ' . ($errors->has($field) ? 'border-red-700' : 'border-line');
@endphp

<div class="flex flex-col gap-6">
    <x-form-input name="title" label="Job title" placeholder="e.g. Senior Laravel Developer" maxlength="255" :value="$jobPosting?->title" required autofocus />

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form-input name="department" label="Department" placeholder="e.g. Engineering" maxlength="255" :value="$jobPosting?->department" />
        <x-form-input name="location" label="Location" placeholder="e.g. Jakarta or Remote" maxlength="255" :value="$jobPosting?->location" />
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="flex flex-col gap-2">
            <label for="status" class="text-sm font-medium">Status</label>
            <select id="status" name="status" class="{{ $textareaClass('status') }} min-h-14" required>
                @foreach (\App\Models\JobPosting::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $jobPosting?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
        </div>
        <x-form-input name="min_experience_years" label="Minimum experience (years)" type="number" min="0" max="60" :value="$jobPosting?->min_experience_years" />
    </div>

    <div class="flex flex-col gap-2">
        <label for="description" class="flex items-center gap-2 text-sm font-medium">Description <span class="text-red-500" aria-hidden="true">*</span></label>
        <textarea id="description" name="description" rows="6" maxlength="20000" required class="{{ $textareaClass('description') }}">{{ old('description', $jobPosting?->description) }}</textarea>
        @error('description')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-2">
        <label for="requirements" class="flex items-center gap-2 text-sm font-medium">Requirements <span class="text-xs font-normal text-muted">Optional</span></label>
        <textarea id="requirements" name="requirements" rows="4" maxlength="20000" class="{{ $textareaClass('requirements') }}">{{ old('requirements', $jobPosting?->requirements) }}</textarea>
        @error('requirements')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-2">
        <label for="required_skills" class="flex items-center gap-2 text-sm font-medium">Required skills <span class="text-red-500" aria-hidden="true">*</span></label>
        <textarea id="required_skills" name="required_skills" rows="2" required placeholder="PHP, Laravel, MySQL" class="{{ $textareaClass('required_skills') }}">{{ $skills('required_skills') }}</textarea>
        <p class="text-xs text-muted">Separate skills with commas or new lines.</p>
        @error('required_skills')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
        @error('required_skills.*')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-2">
        <label for="nice_to_have" class="flex items-center gap-2 text-sm font-medium">Nice to have <span class="text-xs font-normal text-muted">Optional</span></label>
        <textarea id="nice_to_have" name="nice_to_have" rows="2" placeholder="Docker, Redis" class="{{ $textareaClass('nice_to_have') }}">{{ $skills('nice_to_have') }}</textarea>
        <p class="text-xs text-muted">Separate skills with commas or new lines.</p>
        @error('nice_to_have')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
        @error('nice_to_have.*')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
    </div>
</div>
