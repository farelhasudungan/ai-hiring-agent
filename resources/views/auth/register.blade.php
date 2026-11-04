<x-auth-layout title="Make room for possibility." eyebrow="Get started · 01 / 02" description="Create your account. Your company workspace comes next.">
    <form action="{{ route('register.store') }}" method="POST" class="flex flex-col gap-5">
        @csrf

        <x-form-input name="name" label="Full name" autocomplete="name" placeholder="Your full name" maxlength="255" required autofocus />
        <x-form-input name="email" label="Email address" type="email" autocomplete="username" placeholder="you@company.com" maxlength="255" required />
        <x-form-input name="password" label="Password" type="password" autocomplete="new-password" hint="Use at least 8 characters." minlength="8" maxlength="72" required />
        <x-form-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required />

        <x-primary-button class="mt-1">Create account</x-primary-button>
    </form>

    <p class="text-center text-sm text-muted">Already part of the team? <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-medium text-ink underline underline-offset-4">Log in</a></p>
</x-auth-layout>
