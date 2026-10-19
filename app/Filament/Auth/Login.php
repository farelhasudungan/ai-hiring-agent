<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $layout = 'layouts.auth';

    protected string $view = 'filament.auth.login';
}
