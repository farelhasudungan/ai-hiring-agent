@props(['title', 'eyebrow', 'description'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · Hiring agent</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-8 sm:py-12">
        <main class="flex w-full max-w-lg flex-col gap-8 rounded-[40px] bg-paper px-6 py-8 sm:p-12" aria-labelledby="form-heading">
            <div class="flex flex-col gap-3">
                <p class="flex items-center gap-2 text-xs font-bold tracking-widest uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>{{ $eyebrow }}</p>
                <h1 id="form-heading" class="text-4xl font-medium tracking-tight">{{ $title }}</h1>
                <p class="leading-relaxed text-muted">{{ $description }}</p>
            </div>

            {{ $slot }}
        </main>
    </body>
</html>
