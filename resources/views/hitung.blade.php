@extends('layouts.app')
@section('title', 'Kalkulator Dinamis')
@section('content')
<section class="page-width py-16 md:py-24">
    <p class="eyebrow mb-6">SANDBOX / KALKULATOR DINAMIS</p>
    <h1 class="hero-title">Hasil perhitungan.</h1>
    <p class="mt-6 text-lg text-body">Dua angka, satu operasi. Dihitung langsung melalui route Laravel.</p>
    <div class="mt-12 max-w-3xl overflow-hidden rounded-[4px] border border-hairline bg-surface">
        <div class="terminal-bar"><span>calculator / output</span><span>selesai</span></div>
        <div class="p-6 md:p-8"><p class="break-all font-mono text-sm text-muted">$ hitung {{ $angka1 }} {{ $angka2 }} {{ $operasi }}</p><p class="mt-8 text-body">Hasil dari {{ $angka1 }} {{ $operasi }} {{ $angka2 }} adalah</p><p class="mt-4 break-all font-mono text-5xl leading-tight">{{ $hasil }}</p></div>
    </div>
    <div class="mt-8 flex flex-wrap gap-3">
        @foreach (['tambah', 'kurang', 'kali', 'bagi'] as $operation)
            <a class="button-ghost border border-hairline" href="{{ route('hitung', ['angka1' => 10, 'angka2' => 5, 'operasi' => $operation]) }}">10 {{ $operation }} 5 <span aria-hidden="true">↗</span></a>
        @endforeach
    </div>
</section>
@endsection
