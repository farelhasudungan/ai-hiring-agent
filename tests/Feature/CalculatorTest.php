<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculatorTest extends TestCase
{
    /**
     * Memastikan kalkulator menghitung dan menampilkan seluruh operasi.
     */
    public function test_dynamic_calculator_returns_the_calculation_result(): void
    {
        $cases = [
            ['10', '5', 'tambah', '15'],
            ['10', '5', 'kurang', '5'],
            ['10', '5', 'kali', '50'],
            ['10', '5', 'bagi', '2'],
        ];

        foreach ($cases as [$angka1, $angka2, $operasi, $hasil]) {
            $this->get("/hitung/{$angka1}/{$angka2}/{$operasi}")
                ->assertOk()
                ->assertSee("Hasil dari {$angka1} {$operasi} {$angka2} adalah")
                ->assertSee($hasil);
        }
    }
}
