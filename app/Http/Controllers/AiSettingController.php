<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAiModelsRequest;
use App\Http\Requests\UpdateAiSettingRequest;
use App\Models\AiProviderSetting;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AiProviderSettingService;
use App\Services\Ai\Exceptions\AiProviderException;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiSettingController extends Controller
{
    public function edit(Request $request, AiProviderManager $providers): View
    {
        Gate::authorize('view', AiProviderSetting::class);

        return view('settings.ai', [
            'setting' => $request->user()->company->aiProviderSetting,
            'providers' => $providers->providers(),
        ]);
    }

    public function update(UpdateAiSettingRequest $request, AiProviderSettingService $settings): RedirectResponse
    {
        try {
            $settings->save(
                $request->user()->company,
                $request->validated('provider'),
                $request->validated('model'),
                $request->validated('api_key'),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['api_key' => $exception->getMessage()]);
        }

        return redirect()->route('settings.ai.edit')->with('status', 'AI settings saved.');
    }

    public function test(UpdateAiSettingRequest $request, AiProviderSettingService $settings): RedirectResponse
    {
        $apiKey = $request->validated('api_key') ?: $request->user()->company->aiProviderSetting?->api_key;

        if ($apiKey === null) {
            throw ValidationException::withMessages(['api_key' => 'An API key is required.']);
        }

        $retained = $request->validated('api_key') ? ['retained_api_key' => Crypt::encryptString($request->validated('api_key'))] : [];

        try {
            $settings->testConnection($request->validated('provider'), $request->validated('model'), $apiKey);
        } catch (AiProviderException $exception) {
            return redirect()->route('settings.ai.edit')
                ->withInput($request->except('api_key'))
                ->with($retained)
                ->withErrors(['api_key' => $exception->getMessage()]);
        }

        return redirect()->route('settings.ai.edit')
            ->withInput($request->except('api_key'))
            ->with($retained)
            ->with('connection_status', 'Connection successful. Remember to save your settings.');
    }

    /**
     * Lists the models the provider offers for the typed or stored API key.
     */
    public function models(ListAiModelsRequest $request, AiProviderSettingService $settings, AiProviderManager $providers): JsonResponse
    {
        $stored = $request->user()->company->aiProviderSetting;
        $apiKey = $request->validated('api_key') ?: $stored?->api_key;

        if ($apiKey === null && $providers->modelsRequireKey($request->validated('provider'))) {
            throw ValidationException::withMessages(['api_key' => 'Enter an API key to load models.']);
        }

        try {
            $apiKey ??= '';
            $models = $settings->availableModels($request->validated('provider'), $apiKey);
        } catch (AiProviderException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['models' => $models]);
    }
}
