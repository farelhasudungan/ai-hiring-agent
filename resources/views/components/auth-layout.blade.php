@props(['title', 'eyebrow', 'description'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · Roku</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center gap-8 bg-white px-4 py-8 sm:px-8 sm:py-12">
        <a href="{{ route('home') }}" class="flex items-center gap-0.5" aria-label="Roku home">
            <span class="flex size-6 items-center justify-center rounded-xs bg-black text-[15px] leading-[15px] font-bold text-white" aria-hidden="true">R</span>
            <span class="text-sm font-semibold tracking-[-0.35px]" aria-hidden="true">oku</span>
        </a>

        <main class="flex w-full max-w-md flex-col gap-8 rounded-2xl border border-line bg-paper px-6 py-8 sm:p-10" aria-labelledby="form-heading">
            <div class="flex flex-col gap-3">
                <p class="font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">{{ $eyebrow }}</p>
                <h1 id="form-heading" class="text-3xl leading-9 font-medium tracking-[-0.75px]">{{ $title }}</h1>
                <p class="text-sm leading-6 text-zinc-600">{{ $description }}</p>
            </div>

            {{ $slot }}
        </main>

        <x-toast-region />
    </body>
</html>
