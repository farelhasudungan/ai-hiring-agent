<?php

namespace Tests\Feature;

use Tests\TestCase;

class PageTest extends TestCase
{
    public function test_home_displays_the_project_and_theme_toggle(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('Roku')
            ->assertSee('Ranking Kandidat')
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Project')
            ->assertSee('About')
            ->assertDontSee('Coba kalkulator')
            ->assertDontSee('its.academic');
    }

    public function test_about_displays_the_team(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertViewIs('about')
            ->assertSee('04 anggota')
            ->assertSee('Farrel Hasudungan Immanuel Limbong')
            ->assertSee('5025241016')
            ->assertSee('Muhammad Nabil Fauzan')
            ->assertSee('5025241024')
            ->assertSee('Athar Bakhtiar Aziz')
            ->assertSee('5025241136')
            ->assertSee('Hamzah Ali Abdillah')
            ->assertSee('5025241023')
            ->assertDontSee('Laboratorium departemen');
    }

    public function test_navigation_has_panel_actions_and_hero_links_to_the_demo_and_workflow(): void
    {
        $response = $this->get('/')->assertOk();
        $content = $response->getContent();

        preg_match('/<nav\b[^>]*>(.*?)<\/nav>/s', $content, $navigation);
        $this->assertStringNotContainsString('#demo', $navigation[1]);
        $this->assertStringNotContainsString('Cara kerja', $navigation[1]);
        $this->assertStringNotContainsString('data-theme-toggle', $navigation[1]);
        $this->assertStringContainsString('Sign In', $navigation[1]);
        $this->assertStringContainsString('Get started', $navigation[1]);
        $this->assertStringContainsString('href="'.route('filament.admin.auth.login').'"', $navigation[1]);
        $this->assertStringContainsString('href="'.route('filament.admin.pages.dashboard').'"', $navigation[1]);

        preg_match('/<section\b[^>]*aria-labelledby="hero-heading"[^>]*>(.*?)<\/section>/s', $content, $hero);
        $this->assertStringContainsString('Dari kotak masuk,', $hero[1]);
        $this->assertStringContainsString('ke shortlist.', $hero[1]);
        $this->assertSame(1, substr_count($hero[1], 'class="button-primary"'));
        $this->assertStringContainsString('href="#demo"', $hero[1]);
        $this->assertStringContainsString('href="#cara-kerja"', $hero[1]);
        $this->assertStringNotContainsString('data-candidate-demo', $hero[1]);
        $response->assertSee('data-candidate-demo', false)
            ->assertSee('Data ilustrasi')
            ->assertSee('Riset pengguna dan design system sesuai kebutuhan posisi.')
            ->assertSee('Prototyping kuat; kedalaman riset masih perlu digali.')
            ->assertSee('Visual kuat; pengalaman produk dan riset perlu ditinjau.')
            ->assertSee('Roku tidak menerima atau menolak kandidat secara otomatis.');
    }

    public function test_old_project_url_redirects_to_home(): void
    {
        $this->get('/project-idea')->assertStatus(301)->assertRedirect('/');
    }
}
