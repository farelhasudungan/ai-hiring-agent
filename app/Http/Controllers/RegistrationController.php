<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\OnboardingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, OnboardingService $onboarding): RedirectResponse
    {
        $data = $request->validated();
        $user = $onboarding->register($data['name'], $data['email'], $data['password']);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('companies.create');
    }
}
