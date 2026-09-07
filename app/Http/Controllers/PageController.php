<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * PageController
 *
 * Controller utama yang menangani seluruh rute halaman statis.
 */
class PageController extends Controller
{
    /**
     * Menampilkan halaman utama (Home).
     * Rute: GET /
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Menampilkan halaman profil departemen (About).
     * Rute: GET /about
     *
     * @return \Illuminate\View\View
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Menampilkan halaman ide proyek Agentic AI (Project Idea).
     * Rute: GET /project-idea
     *
     * @return \Illuminate\View\View
     */
    public function project()
    {
        return view('project');
    }

    /**
     * Menghitung dua angka berdasarkan operasi yang diberikan.
     * Rute: GET /hitung/{angka1}/{angka2}/{operasi}
     *
     * @return \Illuminate\View\View
     */
    public function hitung($angka1, $angka2, string $operasi)
    {
        $hasil = match ($operasi) {
            'tambah' => $angka1 + $angka2,
            'kurang' => $angka1 - $angka2,
            'kali' => $angka1 * $angka2,
            'bagi' => $angka2 == 0 ? abort(400, 'Pembagian dengan nol tidak diperbolehkan.') : $angka1 / $angka2,
            default => abort(404, 'Operasi tidak ditemukan.'),
        };

        return view('hitung', [
            'angka1' => $angka1,
            'angka2' => $angka2,
            'operasi' => $operasi,
            'hasil' => $hasil,
        ]);
    }
}
