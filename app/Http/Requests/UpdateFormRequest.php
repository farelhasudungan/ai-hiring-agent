<?php

namespace App\Http\Requests;

use App\Models\Form;

class UpdateFormRequest extends StoreFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $form = $this->route('form');

        return $form instanceof Form
            && ($this->user()?->can('update', $form) ?? false);
    }
}
