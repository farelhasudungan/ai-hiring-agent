<x-dashboard-layout title="Company setup">
    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col justify-center gap-8 py-6 sm:py-12">
        <div class="flex items-center gap-3 text-sm text-muted" aria-label="Setup progress: account created, company next">
            <span class="flex size-8 items-center justify-center rounded-full bg-ink text-paper" aria-hidden="true">✓</span>
            <span>Account</span>
            <span class="h-px w-12 bg-line" aria-hidden="true"></span>
            <span class="flex size-8 items-center justify-center rounded-full border border-ink text-ink" aria-hidden="true">2</span>
            <span class="font-medium text-ink">Company</span>
        </div>

        <section class="flex flex-col gap-8 rounded-[40px] bg-paper p-8 sm:p-12" aria-labelledby="company-heading">
            <div class="flex flex-col gap-4">
                <p class="flex items-center gap-2 text-xs font-bold tracking-widest uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>A place for your people</p>
                <h1 id="company-heading" class="max-w-lg text-4xl leading-tight font-medium tracking-tight sm:text-5xl">Let’s make it your company.</h1>
                <p class="max-w-lg leading-relaxed text-muted">Give your workspace a name. You’ll become its admin, with a free plan to get started.</p>
            </div>

            <form action="{{ route('companies.store') }}" method="POST" class="flex flex-col gap-6">
                @csrf

                <x-form-input name="name" label="Company name" autocomplete="organization" placeholder="e.g. Acme Studio" maxlength="255" required autofocus />

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">I’ll do this later</a>
                    <x-primary-button>Create company</x-primary-button>
                </div>
            </form>
        </section>
    </div>
</x-dashboard-layout>
