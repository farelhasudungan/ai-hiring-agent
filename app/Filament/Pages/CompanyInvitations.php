<?php

namespace App\Filament\Pages;

use App\Models\CompanyInvitation;
use App\Models\User;
use BackedEnum;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class CompanyInvitations extends Page
{
    use WithRateLimiting;

    protected static ?string $title = 'Undang anggota tim';

    protected static ?string $navigationLabel = 'Undangan perusahaan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected string $view = 'filament.pages.company-invitations';

    /** @var array<string, mixed> */
    public array $data = [];

    #[Locked]
    public ?string $invitationUrl = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->company_id !== null && $user->role === 'admin';
    }

    public function mount(): void
    {
        $this->form->fill(['role' => 'viewer']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('email')->label('Email penerima')->email()->required()->maxLength(255),
                Select::make('role')->label('Role')->required()->options([
                    'hr' => 'HR',
                    'interviewer' => 'Interviewer',
                    'viewer' => 'Viewer',
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->livewireSubmitHandler('createInvitation')
                ->footer([
                    Actions::make([
                        Action::make('createInvitation')
                            ->label('Buat link undangan')
                            ->submit('createInvitation'),
                    ]),
                ]),
        ]);
    }

    public function createInvitation(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->invitationUrl = null;

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()->danger()->title("Coba lagi dalam {$exception->secondsUntilAvailable} detik.")->send();

            return;
        }

        $data = $this->form->getState();
        $user = Filament::auth()->user();
        $token = Str::random(64);

        $invitation = new CompanyInvitation;
        $invitation->company_id = $user->company_id;
        $invitation->created_by = $user->id;
        $invitation->email = Str::lower(trim($data['email']));
        $invitation->role = $data['role'];
        $invitation->token_hash = hash('sha256', $token);
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        $this->invitationUrl = route('filament.admin.auth.register', ['invitation' => $token]);
    }
}
