@extends('layouts.app')

@section('title', 'Kalkulator Dinamis')

@section('content')
<div class="relative">
    <section class="mx-auto max-w-5xl px-6 pt-12 pb-8 sm:pt-16">
        <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 font-mono text-xs font-medium text-blue-600 dark:text-blue-400">
            <span class="relative flex size-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-blue-500"></span>
            </span>
            <span>Kalkulator Dinamis</span>
        </div>

        <h1 class="text-4xl font-semibold leading-[1.08] tracking-tight text-zinc-900 dark:text-white sm:text-5xl font-display">
            Hasil <span class="gradient-text font-bold">Perhitungan</span>
        </h1>

        <p class="mt-5 max-w-2xl text-base leading-relaxed text-zinc-600 dark:text-zinc-400 sm:text-lg">
            Hasil dari {{ $angka1 }} {{ $operasi }} {{ $angka2 }} adalah
            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $hasil }}</span>
        </p>
    </section>
</div>
@endsection
