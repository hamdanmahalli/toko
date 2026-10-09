<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_menampilkan_halaman_sambutan_untuk_tamu(): void
    {
        $this->get('/')->assertOk();
    }
}
