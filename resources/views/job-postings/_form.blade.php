@php
    $isEdit = isset($jobPosting);
    $selectedFormId = old('form_id', $isEdit ? $jobPosting->form_id : null);
    $selectedStatus = old('status', $isEdit ? $jobPosting->status : 'draft');
    $requiredSkills = old('required_skills', $isEdit ? implode(', ', $jobPosting->required_skills ?? []) : '');
    $niceToHave = old('nice_to_have', $isEdit ? implode(', ', $jobPosting->nice_to_have ?? []) : '');
    $textareaClasses = 'w-full rounded-[20px] border bg-paper px-5 py-4 text-base leading-relaxed placeholder:text-muted/70';
@endphp

<form action="{{ $isEdit ? route('job-postings.update', $jobPosting) : route('job-postings.store') }}" method="POST" class="flex flex-col gap-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-form-input name="title" label="Role title" placeholder="e.g. Senior Laravel Engineer" maxlength="255" required autofocus :value="$isEdit ? $jobPosting->title : null" />

    <div class="grid gap-6 md:grid-cols-2">
        <x-form-input name="department" label="Department" placeholder="e.g. Engineering" maxlength="255" :value="$isEdit ? $jobPosting->department : null" />
        <x-form-input name="location" label="Location" placeholder="e.g. Remote · Jakarta" maxlength="255" :value="$isEdit ? $jobPosting->location : null" />
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="flex flex-col gap-2">
            <label for="status" class="flex items-center gap-2 text-sm font-medium">
                Status
                <span class="text-accent" aria-hidden="true">*</span>
            </label>
            <select id="status" name="status" required class="min-h-14 w-full rounded-[20px] border border-line bg-paper px-5 py-3 text-base">
                @foreach (['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-2">
            <label for="form_id" class="flex items-center gap-2 text-sm font-medium">
                Application form
                <span class="text-xs font-normal text-muted">Optional</span>
            </label>
            <select id="form_id" name="form_id" class="min-h-14 w-full rounded-[20px] border border-line bg-paper px-5 py-3 text-base">
                <option value="">No form attached</option>
                @foreach ($forms as $form)
                    <option value="{{ $form->id }}" @selected((string) $selectedFormId === (string) $form->id)>{{ $form->title }} ({{ $form->status }})</option>
                @endforeach
            </select>
            <p class="text-xs text-muted">Candidates use this form to apply for the role. @can('viewAny', App\Models\Form::class)<a href="{{ route('forms.index') }}" class="underline underline-offset-4 hover:text-ink">Manage forms</a>@endcan</p>
            @error('form_id')
                <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col gap-2">
        <label for="description" class="flex items-center gap-2 text-sm font-medium">
            Description
            <span class="text-accent" aria-hidden="true">*</span>
        </label>
        <textarea id="description" name="description" rows="6" required aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" @if ($errors->has('description')) aria-describedby="description-error" @endif
            class="{{ $textareaClasses }} @error('description') border-red-700 @else border-line @enderror">{{ old('description', $isEdit ? $jobPosting->description : '') }}</textarea>
        @error('description')
            <p id="description-error" role="alert" class="text-sm text-red-800">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-2">
        <label for="requirements" class="flex items-center gap-2 text-sm font-medium">
            Requirements
            <span class="text-xs font-normal text-muted">Optional</span>
        </label>
        <textarea id="requirements" name="requirements" rows="4" aria-invalid="{{ $errors->has('requirements') ? 'true' : 'false' }}" @if ($errors->has('requirements')) aria-describedby="requirements-error" @endif
            class="{{ $textareaClasses }} @error('requirements') border-red-700 @else border-line @enderror">{{ old('requirements', $isEdit ? $jobPosting->requirements : '') }}</textarea>
        @error('requirements')
            <p id="requirements-error" role="alert" class="text-sm text-red-800">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <x-form-input name="required_skills" label="Required skills" hint="Separate skills with commas." placeholder="PHP, Laravel, PostgreSQL" maxlength="2000" :value="$requiredSkills" />
        <x-form-input name="nice_to_have" label="Nice to have" hint="Separate skills with commas." placeholder="Docker, AWS" maxlength="2000" :value="$niceToHave" />
    </div>

    <x-form-input name="min_experience_years" label="Minimum experience" type="number" min="0" max="60" placeholder="e.g. 3" :value="$isEdit ? $jobPosting->min_experience_years : null" />

    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line/60 pt-6">
        <a href="{{ $isEdit ? route('job-postings.show', $jobPosting) : route('job-postings.index') }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">Cancel</a>
        <x-primary-button>{{ $isEdit ? 'Save changes' : 'Create job posting' }}</x-primary-button>
    </div>
</form>
