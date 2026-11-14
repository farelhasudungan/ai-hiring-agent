@php
    $primaryActionUrl = auth()->check() ? route('dashboard') : route('register');

    $metrics = [
        ['value' => '85%', 'label' => 'Faster candidate screening', 'description' => 'From weeks in application backlog down to 4 hours of validated dossiers.'],
        ['value' => '3.2x', 'label' => 'High-signal discovery', 'description' => 'Identify exceptional builders overlooked by generic keyword-matching ATS bots.'],
        ['value' => '100%', 'label' => 'Human governance', 'description' => 'Zero unreviewed rejections. Every pipeline progression requires recruiter approval.'],
    ];

    $capabilities = [
        ['number' => '01', 'title' => 'Autonomous Screening', 'description' => 'Multi-modal verification across GitHub repos, design artifacts, and asynchronous problem solutions.', 'link' => 'Automated signal extraction'],
        ['number' => '02', 'title' => 'Zero Bias by Design', 'description' => 'Personal and demographic markers blinded by default. Pure skill assessment aligned with EEOC and NYC LL144 compliance.', 'link' => 'Objective calibration'],
        ['number' => '03', 'title' => 'Human in Control', 'description' => 'Talent leads preserve full operational jurisdiction. Approve interview passes, challenge submissions, or provide contextual feedback.', 'link' => 'Recruiter authority'],
    ];

    $footerColumns = [
        'Product' => ['Autonomous Screener', 'Resume Parsing', 'Integrations'],
        'Resources' => ['Documentation', 'API Reference', 'Case Studies'],
        'Company' => ['About Us', 'Careers', 'Contact'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Roku · AI hiring agent</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-geist text-black">
        <a href="#main-content" class="sr-only z-50 rounded-xl bg-black px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-4 focus:left-4">Skip to content</a>

        <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md">
            <div class="mx-auto grid h-14 max-w-5xl grid-cols-[1fr_auto] items-center gap-4 px-6 md:grid-cols-[1fr_auto_1fr]">
                <a href="{{ route('home') }}" class="flex items-center gap-0.5 justify-self-start" aria-label="Roku home">
                    <span class="flex size-6 items-center justify-center rounded-xs bg-black text-[15px] leading-[15px] font-bold text-white" aria-hidden="true">R</span>
                    <span class="text-sm font-semibold tracking-[-0.35px]" aria-hidden="true">oku</span>
                </a>

                <nav aria-label="Main navigation" class="hidden items-center gap-7 md:flex">
                    <a href="#product" class="text-xs font-medium text-zinc-600 transition hover:text-black">Product</a>
                    <a href="#metrics" class="text-xs font-medium text-zinc-600 transition hover:text-black">How it works</a>
                    <a href="#features" class="text-xs font-medium text-zinc-600 transition hover:text-black">Features</a>
                    <a href="#stories" class="text-xs font-medium text-zinc-600 transition hover:text-black">Stories</a>
                </nav>

                <div class="flex items-center gap-3 justify-self-end">
                    @auth
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 rounded-xl bg-brand px-4 py-1.5 text-xs font-medium text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)] transition hover:bg-brand/90">
                            Dashboard
                            <img src="{{ asset('images/landing/arrow-right-white.svg') }}" alt="" width="16" height="16" class="size-4">
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-2 py-1 text-xs font-medium text-zinc-600 transition hover:text-black">Log in</a>
                        <a href="{{ route('register') }}" class="flex items-center gap-1.5 rounded-xl bg-brand px-4 py-1.5 text-xs font-medium text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)] transition hover:bg-brand/90">
                            Get started
                            <img src="{{ asset('images/landing/arrow-right-white.svg') }}" alt="" width="16" height="16" class="size-4">
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main id="main-content" class="flex flex-col items-center gap-8 pt-20 pb-20 sm:pt-[88px]" tabindex="-1">
            {{-- Hero --}}
            <section id="product" class="flex w-full max-w-4xl flex-col items-center px-6 text-center">
                <h1 class="text-4xl leading-tight font-medium tracking-[-0.04em] sm:text-5xl lg:text-[64px] lg:leading-16">
                    <span class="block">Screen candidates in seconds.</span>
                    <span class="block font-normal text-zinc-500">You make the final call.</span>
                </h1>

                <p class="mt-6 max-w-xl text-base leading-7 text-zinc-600 sm:text-lg">
                    AI co-pilot parses resumes, verifies engineering footprints, and synthesizes scored dossiers. Recruiters approve with full context.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ $primaryActionUrl }}" class="flex items-center justify-center gap-2 rounded-xl bg-brand px-6 py-2.5 text-xs font-medium text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)] transition hover:bg-brand/90">
                        Start free trial
                        <img src="{{ asset('images/landing/arrow-right-white.svg') }}" alt="" width="16" height="16" class="size-4">
                    </a>
                    <a href="#features" class="flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-[25px] py-[11px] text-xs font-medium text-zinc-800 transition hover:border-zinc-500">
                        View product preview
                    </a>
                </div>
            </section>

            <div class="flex w-full max-w-[684px] justify-center overflow-hidden px-6 py-8 sm:p-16">
                <img src="{{ asset('images/landing/hero-integrations.png') }}" alt="Roku agent connecting Gmail, Sheets, Forms and PDF sources to your hiring tools" width="558" height="223" class="h-auto w-full max-w-[558px]">
            </div>

            {{-- Metrics --}}
            <section id="metrics" aria-label="Results" class="grid w-full max-w-[896px] scroll-mt-20 gap-10 px-6 pt-16 sm:grid-cols-3 sm:gap-8 sm:pt-32">
                @foreach ($metrics as $metric)
                    <div class="flex flex-col gap-[3px]">
                        <p class="text-3xl leading-9 font-medium tracking-[-0.75px]">{{ $metric['value'] }}</p>
                        <p class="text-xs leading-4 font-semibold">{{ $metric['label'] }}</p>
                        <p class="text-xs leading-[19.5px] text-zinc-500">{{ $metric['description'] }}</p>
                    </div>
                @endforeach
            </section>

            {{-- Capabilities --}}
            <section id="features" aria-labelledby="features-heading" class="flex w-full max-w-4xl scroll-mt-14 flex-col gap-14 px-6 pt-24 pb-16 sm:pt-36 sm:pb-24">
                <div class="flex flex-col gap-2">
                    <p class="font-geist-mono text-[11px] leading-[16.5px] tracking-[1.1px] text-zinc-400 uppercase">Capabilities</p>
                    <h2 id="features-heading" class="text-3xl leading-9 font-medium tracking-[-0.75px]">Everything essential. Zero clutter.</h2>
                    <p class="max-w-lg text-sm leading-5 text-zinc-600">Designed for clarity, rigorous evaluation standards, and recruitment velocity.</p>
                </div>

                <div class="grid gap-10 sm:grid-cols-3">
                    @foreach ($capabilities as $capability)
                        <article class="flex flex-col justify-between gap-5 border-t border-zinc-200 pt-5">
                            <div class="flex flex-col gap-[7px] pt-1.5">
                                <p class="font-geist-mono text-xs leading-4 text-zinc-400">{{ $capability['number'] }}</p>
                                <h3 class="pt-[3px] text-sm leading-5 font-semibold tracking-[-0.35px]">{{ $capability['title'] }}</h3>
                                <p class="text-xs leading-[19.5px] text-zinc-600">{{ $capability['description'] }}</p>
                            </div>
                            <p class="flex items-center gap-1.5 pt-6 text-xs leading-4 font-medium">
                                {{ $capability['link'] }}
                                <img src="{{ asset('images/landing/arrow-right-black.svg') }}" alt="" width="16" height="16" class="size-4">
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- Testimonial --}}
            <section id="stories" aria-label="Testimonial" class="flex w-full max-w-3xl scroll-mt-14 flex-col items-center gap-6 px-6 py-20 text-center">
                <p class="font-geist-mono text-xs leading-4 tracking-[1.2px] text-zinc-400 uppercase">— Testimonial</p>
                <figure class="flex flex-col items-center gap-6">
                    <blockquote class="text-lg leading-7 tracking-[-0.5px] text-balance sm:text-xl">
                        "We scaled our technical organization by 60 engineers last quarter without expanding our talent operations headcount. TalentPulse handled the vetting grunt work while engineering managers retained complete decision authority."
                    </blockquote>
                    <figcaption class="flex items-center gap-3 text-left">
                        <img src="{{ asset('images/landing/testimonial-avatar.jpg') }}" alt="Marcus Vance" width="36" height="36" class="size-9 rounded-xl border border-zinc-200 object-cover">
                        <span class="flex flex-col">
                            <span class="text-xs leading-4 font-semibold">Marcus Vance</span>
                            <span class="text-[11px] leading-[16.5px] text-zinc-500">Chief People Officer, CloudVector</span>
                        </span>
                    </figcaption>
                </figure>
            </section>

            {{-- CTA --}}
            <section aria-labelledby="cta-heading" class="flex w-full max-w-xl flex-col items-center px-6 pt-20 text-center">
                <p class="pb-3 font-geist-mono text-[11px] leading-[16.5px] tracking-[1.1px] text-zinc-400 uppercase">Get started</p>
                <h2 id="cta-heading" class="text-3xl font-medium tracking-[-0.9px] sm:text-4xl sm:leading-10">Accelerate your hiring bar today.</h2>
                <p class="pt-3 text-sm leading-5 text-zinc-600">Connect to your existing ATS in less than five minutes. 14-day evaluation with full platform capabilities.</p>

                <div class="flex flex-wrap items-center justify-center gap-3 pt-8">
                    <a href="{{ $primaryActionUrl }}" class="flex items-center justify-center gap-2 rounded-xl bg-brand px-6 py-2.5 text-xs font-medium text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)] transition hover:bg-brand/90">
                        Start 14-day trial
                        <img src="{{ asset('images/landing/arrow-right-white.svg') }}" alt="" width="16" height="16" class="size-4">
                    </a>
                    <a href="{{ $primaryActionUrl }}" class="flex items-center justify-center rounded-xl border border-zinc-300 bg-white px-[25px] py-[11px] text-xs font-medium text-zinc-800 transition hover:border-zinc-500">
                        Book team demo
                    </a>
                </div>

                <ul class="flex flex-wrap items-center justify-center gap-6 pt-8 font-geist-mono text-[11px] leading-[16.5px] text-zinc-500">
                    <li>SOC-2 Certified</li>
                    <li aria-hidden="true">•</li>
                    <li>GDPR Compliant</li>
                    <li aria-hidden="true">•</li>
                    <li>NYC LL144 Audited</li>
                </ul>
            </section>
        </main>

        <footer class="bg-white pt-16 pb-12">
            <div class="mx-auto flex max-w-4xl flex-col gap-14 px-6">
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-5">
                    <div class="col-span-2 flex flex-col gap-3">
                        <p class="flex items-center gap-2">
                            <span class="flex size-4 items-center justify-center rounded-xs bg-black text-[10px] leading-[15px] font-bold text-white" aria-hidden="true">R</span>
                            <span class="text-sm leading-5 font-semibold tracking-[-0.35px]">Roku</span>
                        </p>
                        <p class="max-w-xs text-xs leading-[19.5px] text-zinc-500">Autonomous candidate screening platform with human oversight.</p>
                    </div>

                    @foreach ($footerColumns as $heading => $links)
                        <nav aria-label="{{ $heading }}" class="flex flex-col gap-3">
                            <p class="text-xs leading-4 font-semibold tracking-[-0.3px]">{{ $heading }}</p>
                            <ul class="flex flex-col gap-2.5">
                                @foreach ($links as $link)
                                    <li><a href="#" class="text-xs leading-4 text-zinc-500 transition hover:text-black">{{ $link }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    @endforeach
                </div>

                <div class="flex flex-col-reverse gap-4 pt-2 text-xs leading-4 text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ now()->year }} Roku, Inc. All rights reserved.</p>
                    <ul class="flex gap-6">
                        @foreach (['Privacy', 'Terms', 'Security', 'Cookies'] as $legalLink)
                            <li><a href="#" class="transition hover:text-black">{{ $legalLink }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
