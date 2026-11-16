@php
    $canManage = auth()->user()->can('update', \App\Models\AiProviderSetting::class);
    $selectedProvider = old('provider', $setting?->provider);
    $selectedModel = old('model', $setting?->model);
    $showForm = $canManage && (request()->boolean('edit') || $errors->any() || session()->hasOldInput());
    $keyMask = \App\Models\AiProviderSetting::KEY_MASK;
    $retainedKey = null;
    try {
        $retainedKey = session('retained_api_key') ? \Illuminate\Support\Facades\Crypt::decryptString(session('retained_api_key')) : null;
    } catch (\Illuminate\Contracts\Encryption\DecryptException) {
    }
@endphp

<x-dashboard-layout title="AI settings">
    <section class="flex w-full flex-col gap-8 rounded-2xl bg-paper p-6 sm:p-10" aria-labelledby="ai-settings-heading">
        <div class="flex flex-col gap-3">
            <p class="font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">Settings</p>
            <h1 id="ai-settings-heading" class="text-3xl leading-tight font-medium tracking-tight">AI provider</h1>
            <p class="max-w-xl text-sm leading-relaxed text-muted">Choose which AI screens your candidates. Requests use your own API key, which is stored encrypted and never shown again.</p>
        </div>

        @if ($showForm)
            <form action="{{ route('settings.ai.update') }}" method="POST" class="flex flex-col gap-8">
                @csrf

                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <label for="provider" class="flex items-center gap-2 text-sm font-medium">Provider <span class="text-red-500" aria-hidden="true">*</span></label>
                        <select id="provider" name="provider" required aria-invalid="{{ $errors->has('provider') ? 'true' : 'false' }}" class="min-h-14 w-full rounded-xl border bg-paper px-5 py-3 text-base {{ $errors->has('provider') ? 'border-red-700' : 'border-line' }}">
                            <option value="" @selected(! $selectedProvider) disabled>Select a provider</option>
                            @foreach ($providers as $key => $provider)
                                <option value="{{ $key }}" @selected($selectedProvider === $key)>{{ $provider['label'] }}</option>
                            @endforeach
                        </select>
                        @error('provider')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
                    </div>

                    <div class="relative flex flex-col gap-2">
                        <span id="model-label" class="flex items-center gap-2 text-sm font-medium">Model <span class="text-red-500" aria-hidden="true">*</span></span>
                        <input type="hidden" id="model" name="model" value="{{ $selectedModel }}">
                        <button type="button" id="model-button" aria-haspopup="listbox" aria-expanded="false" aria-controls="model-list" aria-labelledby="model-label model-button" aria-invalid="{{ $errors->has('model') ? 'true' : 'false' }}" class="flex min-h-14 w-full items-center justify-between gap-4 rounded-xl border bg-paper px-5 py-3 text-left text-base {{ $errors->has('model') ? 'border-red-700' : 'border-line' }}">
                            <span id="model-value" class="truncate">{{ $selectedModel ?: 'Select a provider first' }}</span>
                            <svg class="size-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div id="model-panel" hidden class="absolute top-full right-0 left-0 z-20 mt-2 overflow-hidden rounded-xl border border-line bg-paper shadow-lg">
                            <p id="model-query" class="border-b border-line/60 px-5 py-2 font-mono text-xs text-muted" hidden></p>
                            <ul id="model-list" role="listbox" aria-labelledby="model-label" tabindex="-1" class="max-h-72 overflow-y-auto py-2"></ul>
                        </div>
                        <p id="models-status" role="status" class="text-xs text-muted">Models are loaded from your provider. Open the list and start typing to filter.</p>
                        @error('model')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="api_key" class="flex items-center gap-2 text-sm font-medium">API key <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input id="api_key" name="api_key" type="password" value="{{ $retainedKey ?? ($setting ? $keyMask : '') }}" autocomplete="off" maxlength="500" @required(! $setting) aria-invalid="{{ $errors->has('api_key') ? 'true' : 'false' }}" aria-describedby="api_key-hint" class="min-h-14 w-full rounded-xl border bg-paper px-5 py-3 text-base {{ $errors->has('api_key') ? 'border-red-700' : 'border-line' }}">
                        <p id="api_key-hint" class="text-xs text-muted">{{ $setting ? 'Your key is saved and encrypted. Click the field to replace it.' : 'Paste the key from your provider dashboard.' }}</p>
                        @error('api_key')<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="flex flex-wrap items-center gap-3">
                        @php $connected = session('connection_status'); @endphp
                        <button type="submit" formaction="{{ route('settings.ai.test') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border px-6 text-sm font-medium transition {{ $connected ? 'border-green-700 bg-green-50 text-green-800 hover:bg-green-100' : 'border-line hover:bg-zinc-50' }}">
                            @if ($connected)<span aria-hidden="true">✓</span>@endif
                            Test connection
                        </button>
                        @if ($connected)
                            <p role="status" class="text-sm font-medium text-green-800">{{ $connected }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        @if ($setting)
                            <a href="{{ route('settings.ai.edit') }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">Cancel</a>
                        @endif
                        <x-primary-button>Save settings</x-primary-button>
                    </div>
                </div>
            </form>
        @else
            @if ($setting)
                <dl class="grid gap-6 sm:grid-cols-3">
                    <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Provider</dt><dd class="text-sm font-medium">{{ $providers[$setting->provider]['label'] ?? $setting->provider }}</dd></div>
                    <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Model</dt><dd class="text-sm font-medium wrap-anywhere">{{ $setting->model }}</dd></div>
                    <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">API key</dt><dd class="text-sm font-medium">{{ $canManage ? $keyMask : 'Saved' }}</dd></div>
                </dl>
            @else
                <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-muted">No AI provider is configured yet.</p>
            @endif

            @if ($canManage)
                <div><a href="{{ route('settings.ai.edit', ['edit' => 1]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-brand px-6 text-sm font-medium text-white transition hover:bg-brand/90">{{ $setting ? 'Edit settings' : 'Set up AI provider' }}</a></div>
            @else
                <p class="text-sm text-muted">Only admins and HR can change these settings.</p>
            @endif
        @endif
    </section>
    @if ($showForm)
        <script>
            (() => {
                const hasStoredKey = @js($setting !== null);
                const keyMask = @js($keyMask);
                const providers = @js(collect($providers)->map(fn ($provider) => ['requiresKey' => $provider['models_require_key'], 'fallback' => $provider['fallback_models']]));
                const provider = document.getElementById('provider');
                const apiKey = document.getElementById('api_key');
                const field = document.getElementById('model');
                const button = document.getElementById('model-button');
                const valueLabel = document.getElementById('model-value');
                const panel = document.getElementById('model-panel');
                const queryLabel = document.getElementById('model-query');
                const list = document.getElementById('model-list');
                const status = document.getElementById('models-status');
                let models = [];
                let placeholder = 'Select a provider first';
                let visible = [];
                let active = -1;
                let query = '';
                let latest = 0;
                let timer;

                const typedKey = () => (apiKey.value === keyMask ? '' : apiKey.value);

                const isOpen = () => ! panel.hidden;

                const showValue = () => { valueLabel.textContent = field.value || placeholder; };

                const renderList = () => {
                    const needle = query.toLowerCase();
                    visible = models.filter((id) => id.toLowerCase().includes(needle));
                    active = Math.max(0, visible.indexOf(field.value));
                    queryLabel.hidden = query === '';
                    queryLabel.textContent = query;

                    if (visible.length === 0) {
                        list.replaceChildren(Object.assign(document.createElement('li'), { className: 'px-5 py-3 text-sm text-muted', textContent: models.length ? 'No models match' : placeholder }));
                        button.removeAttribute('aria-activedescendant');
                        return;
                    }

                    list.replaceChildren(...visible.map((id, index) => {
                        const item = document.createElement('li');
                        item.id = 'model-option-' + index;
                        item.setAttribute('role', 'option');
                        item.setAttribute('aria-selected', String(id === field.value));
                        item.className = 'cursor-pointer px-5 py-2.5 text-sm break-all';
                        item.textContent = id;
                        item.addEventListener('mousedown', (event) => { event.preventDefault(); choose(id); });
                        return item;
                    }));
                    highlight();
                };

                const highlight = () => {
                    [...list.children].forEach((item, index) => {
                        const on = index === active;
                        item.classList.toggle('bg-zinc-100', on);
                        item.classList.toggle('font-medium', item.getAttribute('aria-selected') === 'true');
                        if (on) {
                            button.setAttribute('aria-activedescendant', item.id);
                            item.scrollIntoView({ block: 'nearest' });
                        }
                    });
                };

                const open = () => {
                    if (button.disabled) { return; }
                    query = '';
                    panel.hidden = false;
                    button.setAttribute('aria-expanded', 'true');
                    renderList();
                };

                const close = () => {
                    panel.hidden = true;
                    button.setAttribute('aria-expanded', 'false');
                    button.removeAttribute('aria-activedescendant');
                    query = '';
                };

                const choose = (id) => {
                    field.value = id;
                    showValue();
                    close();
                    button.focus();
                };

                button.addEventListener('click', () => (isOpen() ? close() : open()));
                button.addEventListener('blur', close);
                panel.addEventListener('mousedown', (event) => event.preventDefault());

                button.addEventListener('keydown', (event) => {
                    if (event.ctrlKey || event.metaKey || event.altKey) { return; }

                    if (event.key === 'Escape' && isOpen()) { event.preventDefault(); close(); return; }
                    if (event.key === 'Tab') { return; }

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        if (! isOpen()) { open(); return; }
                        if (visible.length) { active = (active + (event.key === 'ArrowDown' ? 1 : -1) + visible.length) % visible.length; highlight(); }
                        return;
                    }
                    if (event.key === 'Enter' || (event.key === ' ' && query === '')) {
                        event.preventDefault();
                        if (! isOpen()) { open(); } else if (visible[active]) { choose(visible[active]); }
                        return;
                    }
                    if (event.key === 'Backspace' && isOpen()) { event.preventDefault(); query = query.slice(0, -1); renderList(); return; }

                    if (event.key.length === 1) {
                        event.preventDefault();
                        if (! isOpen()) { open(); }
                        query += event.key;
                        renderList();
                    }
                });

                const setModels = (next, emptyText) => {
                    models = next;
                    placeholder = emptyText;
                    if (field.value && ! models.includes(field.value) && models.length) { models = [field.value, ...models]; }
                    if (! field.value || ! models.includes(field.value)) { field.value = ''; }
                    showValue();
                    if (isOpen()) { renderList(); }
                };

                const load = async () => {
                    const request = ++latest;

                    if (! provider.value) {
                        setModels([], 'Select a provider first');
                        status.textContent = 'Models are loaded from your provider.';
                        return;
                    }

                    const config = providers[provider.value];
                    if (config.requiresKey && ! typedKey() && ! hasStoredKey) {
                        setModels(config.fallback, 'Enter your API key to load models');
                        status.textContent = 'Showing common models. Enter your API key to load the full list from your provider.';
                        return;
                    }

                    status.textContent = 'Loading models…';
                    button.disabled = true;
                    try {
                        const response = await fetch(@js(route('settings.ai.models')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                            body: JSON.stringify({ provider: provider.value, api_key: typedKey() }),
                        });
                        const data = await response.json();
                        if (request !== latest) { return; }
                        if (! response.ok) {
                            throw new Error(data.message ?? data.errors?.api_key?.[0] ?? 'Could not load models.');
                        }
                        setModels(data.models, 'No models available');
                        status.textContent = data.models.length + ' models available from your provider. Open the list and start typing to filter.';
                    } catch (error) {
                        if (request === latest) { setModels([], 'Models could not be loaded'); status.textContent = error.message; }
                    } finally {
                        if (request === latest) { button.disabled = false; }
                    }
                };

                provider.addEventListener('change', () => { field.value = ''; load(); });
                apiKey.addEventListener('focus', () => { if (apiKey.value === keyMask) { apiKey.value = ''; } });
                apiKey.addEventListener('blur', () => { if (apiKey.value === '' && hasStoredKey) { apiKey.value = keyMask; clearTimeout(timer); load(); } });
                apiKey.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 700); });

                if (field.value) { models = [field.value]; }
                load();
            })();
        </script>
    @endif
</x-dashboard-layout>
