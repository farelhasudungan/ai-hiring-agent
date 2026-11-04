<x-auth-layout title="Welcome back." eyebrow="Your hiring workspace" description="Log in to pick up where you left off.">
    <form action="{{ route('login.store') }}" method="POST" class="flex flex-col gap-6">
        @csrf

        <x-form-input name="email" label="Email address" type="email" autocomplete="username" placeholder="you@company.com" required autofocus />
        <x-form-input name="password" label="Password" type="password" autocomplete="current-password" required />

        <label class="inline-flex min-h-11 w-fit items-center gap-3 text-sm text-muted">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-ink">
            Keep me logged in
        </label>

        <x-primary-button>Log in</x-primary-button>
    </form>

    <p class="text-center text-sm text-muted">New here? <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center font-medium text-ink underline underline-offset-4">Create an account</a></p>
</x-auth-layout>
