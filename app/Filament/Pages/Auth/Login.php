<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getExtraBodyAttributes(): array
    {
        return [...parent::getExtraBodyAttributes(), 'class' => 'hiring-login', 'lang' => 'id'];
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Masuk';
    }

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Selamat datang.';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Masuk ke workspace rekrutmen Anda dengan akun tim.';
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Email kerja')
            ->placeholder('nama@perusahaan.com')
            ->autocomplete('email')
            ->autofocus(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Password')
            ->placeholder('Masukkan password Anda')
            ->hint(null)
            ->hintAction(
                Action::make('requestPasswordReset')
                    ->label('Lupa password?')
                    ->url(fn (): ?string => filament()->getRequestPasswordResetUrl())
                    ->visible(fn (): bool => filament()->hasPasswordReset()),
            );
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()->label('Ingat saya');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Masuk')
            ->icon('heroicon-m-arrow-right')
            ->iconPosition(IconPosition::After);
    }
}
