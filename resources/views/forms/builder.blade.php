<x-dashboard-layout :title="'Builder · '.$form->title">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <div class="flex flex-col gap-4">
            <a href="{{ route('forms.show', $form) }}" class="inline-flex w-fit items-center gap-2 text-sm text-muted underline-offset-4 hover:underline"><span aria-hidden="true">←</span> Back to form</a>
            <div class="flex flex-col gap-3">
                <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>Form builder</p>
                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $form->title }}</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-muted sm:text-base">Group questions into sections and reorder them. Editing is still allowed after responses exist, but questions with responses cannot be removed.</p>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-[20px] border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-800">
                <p class="font-medium">Some fields need your attention.</p>
                <p class="mt-1">Check the highlighted fields below. Everything you entered has been kept.</p>
                @foreach (['sections', 'schema'] as $errorKey)
                    @if ($errors->has($errorKey))
                        <p class="mt-3 font-medium">{{ $errors->first($errorKey) }}</p>
                    @endif
                @endforeach
            </div>
        @endif

        <form action="{{ route('forms.schema.update', $form) }}" method="POST" data-form-builder data-choice-types='@json($choiceTypes)' class="flex flex-col gap-6">
            @csrf
            @method('PUT')

            <div data-sections class="flex flex-col gap-6">
                @foreach ($sections as $sectionIndex => $section)
                    <section data-section data-section-index="{{ $sectionIndex }}" class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-5 sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-[11px] font-semibold tracking-widest text-muted uppercase">Section</p>
                            <div class="flex items-center gap-1">
                                <button type="button" data-move-section-up class="flex size-8 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Move section up">↑</button>
                                <button type="button" data-move-section-down class="flex size-8 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Move section down">↓</button>
                                <button type="button" data-remove-section class="ml-1 rounded-full px-3 py-1 text-xs text-red-800 transition hover:bg-red-50">Remove section</button>
                            </div>
                        </div>

                        <input type="hidden" name="sections[{{ $sectionIndex }}][id]" value="{{ $section->id }}">

                        @error("sections.$sectionIndex.id")
                            <p role="alert" class="rounded-[12px] bg-red-50 px-3 py-2 text-xs text-red-800">{{ $message }}</p>
                        @enderror

                        @php($sectionTitleError = $errors->has("sections.$sectionIndex.title"))

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium">Section title <span class="text-accent" aria-hidden="true">*</span></label>
                            <input name="sections[{{ $sectionIndex }}][title]" value="{{ $section->title }}" placeholder="e.g. Personal information"
                                aria-invalid="{{ $sectionTitleError ? 'true' : 'false' }}"
                                @if ($sectionTitleError) aria-describedby="section-{{ $sectionIndex }}-title-error" @endif
                                @class(['min-h-12 w-full rounded-[16px] border bg-paper px-4 py-2 text-sm', 'border-red-700' => $sectionTitleError, 'border-line' => ! $sectionTitleError])>
                            @if ($sectionTitleError)
                                <p id="section-{{ $sectionIndex }}-title-error" role="alert" class="text-xs text-red-800">{{ $errors->first("sections.$sectionIndex.title") }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium">Section description</label>
                            <textarea name="sections[{{ $sectionIndex }}][description]" rows="2" class="w-full rounded-[16px] border border-line bg-paper px-4 py-3 text-sm">{{ $section->description }}</textarea>
                        </div>

                        @php($nextFieldIndex = $section->fields->isEmpty() ? 0 : $section->fields->keys()->map(fn ($key): int => (int) $key)->max() + 1)

                        <div data-fields data-next-index="{{ $nextFieldIndex }}" class="flex flex-col gap-3">
                            @foreach ($section->fields as $fieldIndex => $field)
                                @include('forms._field-form', ['prefix' => "sections[$sectionIndex][fields][$fieldIndex]", 'field' => $field])
                            @endforeach
                        </div>

                        <button type="button" data-add-field class="inline-flex w-fit min-h-10 items-center gap-2 rounded-full border border-line px-4 py-2 text-xs font-medium transition hover:bg-canvas">+ Add question</button>
                    </section>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line/60 pt-6">
                <a href="{{ route('forms.show', $form) }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">Cancel</a>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-add-section class="inline-flex min-h-12 items-center rounded-[20px] border border-line bg-paper px-5 py-3 text-sm font-medium transition hover:bg-canvas">+ Add section</button>
                    <x-primary-button>Save form</x-primary-button>
                </div>
            </div>
        </form>

        <template id="section-template">
            <section data-section data-section-index="__SECTION__" class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold tracking-widest text-muted uppercase">Section</p>
                    <div class="flex items-center gap-1">
                        <button type="button" data-move-section-up class="flex size-8 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Move section up">↑</button>
                        <button type="button" data-move-section-down class="flex size-8 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas" aria-label="Move section down">↓</button>
                        <button type="button" data-remove-section class="ml-1 rounded-full px-3 py-1 text-xs text-red-800 transition hover:bg-red-50">Remove section</button>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium">Section title <span class="text-accent" aria-hidden="true">*</span></label>
                    <input name="sections[__SECTION__][title]" placeholder="e.g. Personal information" class="min-h-12 w-full rounded-[16px] border border-line bg-paper px-4 py-2 text-sm">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium">Section description</label>
                    <textarea name="sections[__SECTION__][description]" rows="2" class="w-full rounded-[16px] border border-line bg-paper px-4 py-3 text-sm"></textarea>
                </div>

                <div data-fields data-next-index="1" class="flex flex-col gap-3">
                    @include('forms._field-form', ['prefix' => 'sections[__SECTION__][fields][0]', 'field' => null])
                </div>

                <button type="button" data-add-field class="inline-flex w-fit min-h-10 items-center gap-2 rounded-full border border-line px-4 py-2 text-xs font-medium transition hover:bg-canvas">+ Add question</button>
            </section>
        </template>

        <template id="field-template">
            @include('forms._field-form', ['prefix' => 'sections[__SECTION__][fields][__FIELD__]', 'field' => null])
        </template>
    </div>
</x-dashboard-layout>
