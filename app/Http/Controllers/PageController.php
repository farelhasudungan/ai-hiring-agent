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
}
