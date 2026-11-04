<x-layout title="Your workspace">
    <div class="flex flex-1 flex-col gap-8 py-4 sm:py-8">
        <div class="flex flex-col gap-4">
            <p class="flex items-center gap-2 text-xs font-bold tracking-widest uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>Your workspace</p>
            <h1 class="text-4xl leading-tight font-medium tracking-tight sm:text-5xl">Welcome, {{ $user->name }}.</h1>
            <p class="max-w-xl leading-relaxed text-muted">A little less administration. A little more possibility.</p>
        </div>

        @if ($company)
            <section class="grid gap-8 rounded-[40px] bg-paper p-8 sm:p-12 md:grid-cols-[1fr_auto]" aria-labelledby="company-heading">
                <div class="flex min-w-0 flex-col gap-4">
                    <p class="text-sm text-muted">Your company</p>
                    <h2 id="company-heading" class="text-3xl font-medium tracking-tight wrap-anywhere">{{ $company->name }}</h2>
                    <p class="text-sm text-muted">Your company workspace is ready. Job postings and candidate workflows will come next.</p>
                </div>
                <dl class="flex flex-wrap gap-8 md:flex-col md:gap-4">
                    <div class="flex flex-col gap-1">
                        <dt class="text-xs text-muted">Your role</dt>
                        <dd class="text-sm font-medium capitalize">{{ $user->role }}</dd>
                    </div>
                    <div class="flex flex-col gap-1">
                        <dt class="text-xs text-muted">Company plan</dt>
                        <dd class="text-sm font-medium capitalize">{{ $company->plan }}</dd>
                    </div>
                </dl>
            </section>
        @else
            <section class="flex flex-col items-start gap-6 rounded-[40px] bg-paper p-8 sm:p-12" aria-labelledby="company-heading">
                <span class="flex size-16 items-center justify-center rounded-full bg-canvas text-3xl" aria-hidden="true">+</span>
                <div class="flex max-w-lg flex-col gap-3">
                    <h2 id="company-heading" class="text-3xl font-medium tracking-tight">Your company belongs here.</h2>
                    <p class="leading-relaxed text-muted">Your account is ready. Create a company to set up a dedicated space for your hiring team.</p>
                </div>
                <a href="{{ route('companies.create') }}" class="inline-flex min-h-14 items-center gap-6 rounded-[20px] bg-ink px-6 py-3 font-medium text-canvas transition hover:bg-ink/85">Create your company <span aria-hidden="true">↗</span></a>
            </section>
        @endif
    </div>
</x-layout>
