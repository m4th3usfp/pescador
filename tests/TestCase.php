<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // dd('env APP_ENV = ' . env('APP_ENV'));
        // dd('config app.env = ' . config('app.env'));

        if (config('app.env') !== 'testing') {
            throw new \Exception('Testes devem ser rodados apenas no ambiente de teste!');
        }
    }
}
