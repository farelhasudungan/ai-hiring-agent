<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculatorTest extends TestCase
{
    /**
     * Kalkulator tidak lagi menjadi bagian website proyek.
     */
    public function test_calculator_route_is_no_longer_available(): void
    {
        $this->get('/hitung/10/5/tambah')->assertNotFound();
    }
}
