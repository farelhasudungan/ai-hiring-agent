<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * PageController
 *
 * Controller utama yang menangani seluruh rute halaman statis.
 */
class PageController extends Controller
{
    /**
     * Menampilkan proyek Recruiting and Hiring Agents sebagai beranda.
     * Rute: GET /
     *
     * @return View
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Menampilkan informasi tim pengembang (About).
     * Rute: GET /about
     *
     * @return View
     */
    public function about()
    {
        return view('about');
    }
}
