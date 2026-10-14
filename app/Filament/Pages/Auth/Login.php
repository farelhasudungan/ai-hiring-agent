<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    public function getExtraBodyAttributes(): array
    {
        return [...parent::getExtraBodyAttributes(), 'class' => 'hiring-login'];
    }

    public function getHeading(): string
    {
        return 'Welcome back';
    }

    public function getSubheading(): string
    {
        return 'Sign in to your Hiring Agents workspace.';
    }
}
