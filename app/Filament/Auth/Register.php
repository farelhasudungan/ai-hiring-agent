<?php

namespace App\Filament\Auth;

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use SensitiveParameter;

class Register extends SimplePage
{
    use WithRateLimiting;

    protected static string $layout = 'layouts.auth';

    protected string $view = 'filament.auth.register';

    #[Locked]
    public int $step = 1;

    /** @var array<string, mixed> */
    public array $data = [
        'email' => '',
        'name' => '',
        'password' => '',
        'company_mode' => 'create',
        'company_name' => '',
        'invitation' => '',
    ];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $this->redirect(Filament::getUrl());

            return;
        }

        $invitation = request()->query('invitation');

        if (is_string($invitation) && preg_match('/\A[a-zA-Z0-9]{64}\z/', $invitation)) {
            $this->data['invitation'] = $invitation;
            $this->data['company_mode'] = 'join';
        }
    }

    public function nextStep(): void
    {
        if ($this->step >= 3 || ! $this->allowAttempt(10, 'nextStep')) {
            return;
        }

        $this->normalizeInput();
        $this->validate($this->step === 1 ? $this->emailRules() : $this->accountRules());
        $this->step++;
        $this->resetValidation();
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
        $this->resetValidation();
    }

    public function register(): ?RegistrationResponse
    {
        abort_if(Filament::auth()->check(), 403);

        if ($this->step !== 3 || ! $this->allowAttempt(3, 'register')) {
            return null;
        }

        $this->normalizeInput();

        try {
            $validated = $this->validate([
                ...$this->emailRules(),
                ...$this->accountRules(),
                'data.company_mode' => ['required', Rule::in(['create', 'join'])],
                'data.company_name' => ['exclude_unless:data.company_mode,create', 'required', 'string', 'max:255'],
                'data.invitation' => ['exclude_unless:data.company_mode,join', 'required', 'string', 'regex:/\A[a-zA-Z0-9]{64}\z/'],
            ]);
        } catch (ValidationException $exception) {
            $this->step = match (true) {
                isset($exception->errors()['data.email']) => 1,
                isset($exception->errors()['data.name']), isset($exception->errors()['data.password']) => 2,
                default => 3,
            };

            throw $exception;
        }

        $user = $this->createAccount($validated['data']);
        $this->data['password'] = '';

        event(new Registered($user));
        Filament::auth()->login($user);
        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    public function getTitle(): string
    {
        return 'Sign Up';
    }

    /** @return array<string, array<mixed>> */
    protected function emailRules(): array
    {
        return ['data.email' => ['bail', 'required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')]];
    }

    /** @return array<string, array<mixed>> */
    protected function accountRules(): array
    {
        return [
            'data.name' => ['required', 'string', 'max:255'],
            'data.password' => ['required', 'string', 'max:255', Password::defaults()],
        ];
    }

    protected function allowAttempt(int $limit, string $method): bool
    {
        try {
            $this->rateLimit($limit, method: $method);
        } catch (TooManyRequestsException $exception) {
            $this->addError('attempt', "Terlalu banyak percobaan. Coba lagi dalam {$exception->secondsUntilAvailable} detik.");

            return false;
        }

        return true;
    }

    protected function normalizeInput(): void
    {
        foreach (['email', 'name', 'company_name', 'invitation'] as $field) {
            if (is_string($this->data[$field] ?? null)) {
                $this->data[$field] = trim($this->data[$field]);
            }
        }

        if (is_string($this->data['email'] ?? null)) {
            $this->data['email'] = Str::lower($this->data['email']);
        }

        $invitation = $this->data['invitation'] ?? null;

        if (is_string($invitation) && filter_var($invitation, FILTER_VALIDATE_URL)) {
            parse_str(parse_url($invitation, PHP_URL_QUERY) ?? '', $query);
            $this->data['invitation'] = $query['invitation'] ?? '';
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createAccount(#[SensitiveParameter] array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $invitation = null;

            if ($data['company_mode'] === 'join') {
                $invitation = CompanyInvitation::query()
                    ->where('token_hash', hash('sha256', $data['invitation']))
                    ->lockForUpdate()
                    ->first();

                if (! $invitation || ! $invitation->canBeAcceptedBy($data['email'])) {
                    throw ValidationException::withMessages([
                        'data.invitation' => 'Undangan tidak valid, sudah digunakan, kedaluwarsa, atau bukan untuk email ini.',
                    ]);
                }

                $companyId = $invitation->company_id;
                $role = $invitation->role;
            } else {
                $company = Company::create([
                    'name' => $data['company_name'],
                    'slug' => Str::substr(Str::slug($data['company_name']) ?: 'company', 0, 240).'-'.Str::lower(Str::random(12)),
                    'plan' => 'free',
                ]);

                $companyId = $company->id;
                $role = 'admin';
            }

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->company_id = $companyId;
            $user->role = $role;
            $user->save();

            if ($invitation) {
                $invitation->accepted_at = now();
                $invitation->save();
            }

            return $user;
        });
    }
}
