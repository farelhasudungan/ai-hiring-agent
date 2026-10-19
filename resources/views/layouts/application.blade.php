<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Form lamaran — Roku')</title>
    <x-theme-script />
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..500&family=Source+Serif+4:ital,wght@0,400;1,400&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="min-h-dvh bg-canvas font-sans text-ink antialiased">
    <main id="main">@yield('content')</main>
</body>
</html>
