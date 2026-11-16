<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $user->load('company');

        $companyId = $user->company_id;

        return view('dashboard', [
            'user' => $user,
            'company' => $user->company,
            'jobPostingCount' => $companyId ? JobPosting::query()->where('company_id', $companyId)->count() : 0,
            'applicationCount' => $companyId ? Application::query()->where('company_id', $companyId)->count() : 0,
        ]);
    }
}
