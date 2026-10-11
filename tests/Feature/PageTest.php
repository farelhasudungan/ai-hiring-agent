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
            ->assertSee('Recruiting and Hiring Agents')
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

    public function test_navigation_has_no_demo_button_and_hero_has_one_primary_action(): void
    {
        $response = $this->get('/')->assertOk();
        $content = $response->getContent();

        preg_match('/<nav\b[^>]*>(.*?)<\/nav>/s', $content, $navigation);
        $this->assertStringNotContainsString('#demo', $navigation[1]);
        $this->assertStringNotContainsString('Jelajahi demo', $navigation[1]);

        preg_match('/<section\b[^>]*aria-labelledby="hero-heading"[^>]*>(.*?)<\/section>/s', $content, $hero);
        $this->assertStringContainsString('Temukan talenta tepat.', $hero[1]);
        $this->assertSame(1, substr_count($hero[1], '<a '));
        $this->assertStringNotContainsString('data-candidate-demo', $hero[1]);
        $response->assertSee('data-candidate-demo', false);
    }

    public function test_old_project_url_redirects_to_home(): void
    {
        $this->get('/project-idea')->assertStatus(301)->assertRedirect('/');
    }
}
