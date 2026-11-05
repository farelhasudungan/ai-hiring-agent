<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Http\Requests\UpdateFormSchemaRequest;
use App\Http\Requests\UpdateFormStatusRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use App\Repositories\FormRepository;
use App\Services\FormService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(Request $request, FormRepository $repository): View
    {
        Gate::authorize('viewAny', Form::class);

        $user = $request->user();
        $status = $request->query('status');
        $status = in_array($status, FormService::STATUSES, true) ? $status : null;

        return view('forms.index', [
            'forms' => $repository->paginateForCompany($user->company_id, $status),
            'statusCounts' => $repository->statusCounts($user->company_id),
            'activeStatus' => $status,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Form::class);

        return view('forms.create');
    }

    public function store(StoreFormRequest $request, FormService $service): RedirectResponse
    {
        $form = $service->create($request->user(), $request->validated());

        return redirect()->route('forms.show', $form)
            ->with('status', 'Form created. Add sections and fields to finish it.');
    }

    public function show(Form $form): View
    {
        Gate::authorize('view', $form);

        $form->load(['sections.fields.options', 'jobPostings'])
            ->loadCount(['fields', 'submissions', 'jobPostings']);

        return view('forms.show', [
            'form' => $form,
        ]);
    }

    public function edit(Form $form): View
    {
        Gate::authorize('update', $form);

        return view('forms.edit', [
            'form' => $form,
        ]);
    }

    public function update(UpdateFormRequest $request, Form $form, FormService $service): RedirectResponse
    {
        $service->update($request->user(), $form, $request->validated());

        return redirect()->route('forms.show', $form)
            ->with('status', 'Form details updated.');
    }

    public function updateStatus(UpdateFormStatusRequest $request, Form $form, FormService $service): RedirectResponse
    {
        $service->updateStatus($request->user(), $form, $request->validated('status'));

        return back()->with('status', 'Form status updated.');
    }

    public function builder(Request $request, Form $form): View
    {
        Gate::authorize('update', $form);

        $form->load('sections.fields.options');

        $oldSections = $request->old('sections');

        return view('forms.builder', [
            'form' => $form,
            'sections' => is_array($oldSections) ? $this->hydrateSections($oldSections) : $form->sections,
            'fieldTypes' => FormService::FIELD_TYPES,
            'choiceTypes' => FormService::CHOICE_TYPES,
        ]);
    }

    public function updateSchema(UpdateFormSchemaRequest $request, Form $form, FormService $service): RedirectResponse
    {
        try {
            $service->syncSchema($request->user(), $form, $request->validated('sections'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['schema' => $exception->getMessage()]);
        }

        return redirect()->route('forms.show', $form)
            ->with('status', 'Form structure saved.');
    }

    public function destroy(Request $request, Form $form, FormService $service): RedirectResponse
    {
        Gate::authorize('delete', $form);

        $deletedSubmissions = $service->delete($request->user(), $form);

        $message = $deletedSubmissions > 0
            ? "Form deleted along with {$deletedSubmissions} response(s)."
            : 'Form deleted.';

        return redirect()->route('forms.index')->with('status', $message);
    }

    /**
     * Rebuild the unsaved builder state after a failed schema save.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return Collection<int, FormSection>
     */
    private function hydrateSections(array $sections): Collection
    {
        return collect($sections)->map(function (array $sectionData, int|string $sectionIndex): FormSection {
            $section = new FormSection([
                'title' => $sectionData['title'] ?? '',
                'description' => $sectionData['description'] ?? null,
                'sort_order' => (int) $sectionIndex,
            ]);
            $section->id = $sectionData['id'] ?? null;

            $fields = collect($sectionData['fields'] ?? [])->map(function (array $fieldData, int|string $fieldIndex): FormField {
                $field = new FormField([
                    'field_key' => $fieldData['field_key'] ?? '',
                    'label' => $fieldData['label'] ?? '',
                    'type' => $fieldData['type'] ?? 'short_text',
                    'help_text' => $fieldData['help_text'] ?? null,
                    'placeholder' => $fieldData['placeholder'] ?? null,
                    'is_required' => filter_var($fieldData['is_required'] ?? false, FILTER_VALIDATE_BOOL),
                    'maps_to' => $fieldData['maps_to'] ?? null,
                    'sort_order' => (int) $fieldIndex,
                ]);
                $field->id = $fieldData['id'] ?? null;

                $options = collect($fieldData['options'] ?? [])->map(fn (array $optionData, int|string $optionIndex): FormFieldOption => new FormFieldOption([
                    'label' => $optionData['label'] ?? '',
                    'value' => $optionData['value'] ?? '',
                    'sort_order' => (int) $optionIndex,
                ]));

                $field->setRelation('options', $options);

                return $field;
            });

            $section->setRelation('fields', $fields);

            return $section;
        });
    }
}
