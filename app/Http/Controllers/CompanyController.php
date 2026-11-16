<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Services\AuthService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Company::class);

        return view('companies.create');
    }

    public function store(StoreCompanyRequest $request, AuthService $auth): RedirectResponse
    {
        try {
            $auth->createCompany($request->user(), $request->validated('name'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['name' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard')->with('status', 'Your company is ready. Welcome aboard!');
    }
}
