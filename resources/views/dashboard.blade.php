<x-dashboard-layout title="Overview">
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="flex min-w-0 flex-col gap-3">
                <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>A little more possibility</p>
                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl xl:text-5xl">Welcome, {{ $user->name }}.</h1>
                <p class="text-sm leading-relaxed text-muted sm:text-base">Here’s where your next chapter in hiring begins.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-line bg-paper px-4 py-2 text-xs"><span class="size-1.5 rounded-full bg-ink" aria-hidden="true"></span>{{ $company ? 'Workspace ready' : 'Let’s get you set up' }}</span>
        </div>

        <section class="relative overflow-hidden rounded-[32px] bg-ink p-6 text-paper sm:p-8 xl:p-10" aria-labelledby="workspace-heading">
            <div class="pointer-events-none absolute -top-20 -right-16 size-80 rounded-full border border-paper/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute top-4 -right-28 size-80 rounded-full border border-orbit/40" aria-hidden="true"></div>
            <div class="relative flex max-w-xl flex-col items-start gap-4">
                <p class="text-xs font-medium tracking-widest text-paper/60 uppercase">Built around your people</p>
                <h2 id="workspace-heading" class="text-2xl leading-tight font-medium tracking-tight sm:text-3xl">A home for your hiring team.</h2>
                <p class="max-w-md text-sm leading-relaxed text-paper/70">{{ $company ? 'Your company is set up. Keep your workspace and account details together as your team takes its next steps.' : 'Start with your company. Create a dedicated workspace and take the first step toward building a great team.' }}</p>
                <a href="{{ $company ? '#company' : route('companies.create') }}" class="mt-2 inline-flex min-h-12 items-center gap-6 rounded-[20px] bg-paper px-5 py-3 text-sm font-medium text-ink transition hover:bg-canvas">{{ $company ? 'View your company' : 'Create your company' }} <span aria-hidden="true">↗</span></a>
            </div>
        </section>

        <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        @if ($company)
            <section id="company" class="flex min-w-0 scroll-mt-6 flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="company-heading">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-sm font-medium">Your company</p>
                    <span class="rounded-full bg-canvas px-3 py-1.5 text-xs capitalize">{{ $company->plan }} plan</span>
                </div>
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-[20px] bg-canvas text-2xl font-medium" aria-hidden="true">{{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}</span>
                    <div class="flex min-w-0 flex-col gap-2">
                        <h2 id="company-heading" class="text-2xl font-medium tracking-tight wrap-anywhere">{{ $company->name }}</h2>
                        <p class="text-sm text-muted">Your team’s dedicated workspace</p>
                    </div>
                </div>
                <p class="mt-auto border-t border-line/60 pt-5 text-sm leading-relaxed text-muted">Your company workspace is ready. Job postings and candidate workflows will come next.</p>
            </section>
        @else
            <section id="company" class="flex min-w-0 scroll-mt-6 flex-col items-start gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="company-heading">
                <p class="text-sm font-medium">Your company</p>
                <div class="flex items-center gap-4">
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-[20px] border border-dashed border-line bg-canvas text-3xl text-muted" aria-hidden="true">+</span>
                    <h2 id="company-heading" class="text-2xl font-medium tracking-tight">Make it your workspace.</h2>
                </div>
                <p class="text-sm leading-relaxed text-muted">Add your company name to get started. You’ll become its admin on the free plan.</p>
                <a href="{{ route('companies.create') }}" class="inline-flex min-h-11 items-center gap-3 text-sm font-medium underline underline-offset-4">Set up company <span aria-hidden="true">↗</span></a>
            </section>
        @endif

            <section id="account" class="flex min-w-0 scroll-mt-6 flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="account-heading">
                <h2 id="account-heading" class="text-sm font-medium">Account overview</h2>
                <dl class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Full name</dt><dd class="text-sm font-medium wrap-anywhere">{{ $user->name }}</dd></div>
                    <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Email address</dt><dd class="text-sm font-medium wrap-anywhere">{{ $user->email }}</dd></div>
                    <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Workspace role</dt><dd class="text-sm font-medium capitalize">{{ $company ? $user->role : 'Awaiting company setup' }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="flex flex-col gap-6 rounded-[32px] border border-line/60 p-6 sm:p-8" aria-labelledby="setup-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="setup-heading" class="text-lg font-medium tracking-tight">A strong start</h2>
                <span class="text-xs text-muted">{{ $company ? '2 of 2 steps complete' : '1 of 2 steps complete' }}</span>
            </div>
            <ol class="grid gap-6 md:grid-cols-2">
                <li class="flex items-start gap-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink text-sm text-paper" aria-hidden="true">✓</span>
                    <div class="flex flex-col gap-2"><h3 class="text-sm font-medium">Create your account <span class="sr-only">— complete</span></h3><p class="text-sm leading-relaxed text-muted">You’re in. Your personal account is ready.</p></div>
                </li>
                <li class="flex items-start gap-4">
                    <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full text-sm', 'bg-ink text-paper' => $company, 'border border-line text-muted' => ! $company]) aria-hidden="true">{{ $company ? '✓' : '2' }}</span>
                    <div class="flex flex-col gap-2"><h3 class="text-sm font-medium">Set up your company <span class="sr-only">— {{ $company ? 'complete' : 'pending' }}</span></h3><p class="text-sm leading-relaxed text-muted">{{ $company ? 'Your team now has a place to call home.' : 'Give your hiring workspace a name of its own.' }}</p></div>
                </li>
            </ol>
        </section>
        <p class="pb-2 text-center text-xs text-muted sm:text-left">Thoughtful hiring starts here. <span class="hidden sm:inline">AI-assisted. Human-led.</span></p>
    </div>
</x-dashboard-layout>
