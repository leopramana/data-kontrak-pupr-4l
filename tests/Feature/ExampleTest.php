<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_bisa_dibuka(): void
    {
        $this->get('/')->assertOk();
        $this->get('/tentang')->assertOk();
        $this->get('/masuk')->assertOk();
    }
}
