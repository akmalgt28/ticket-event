<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ProductionErrorHandlingTest extends TestCase
{
    public function test_api_exception_does_not_expose_debug_details_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);
        Route::get('/test-production-error', fn (): never => throw new RuntimeException('secret internal failure'));

        $response = $this->getJson('/test-production-error');

        $response->assertServerError();
        $this->assertStringNotContainsString('secret internal failure', $response->getContent());
        $this->assertStringNotContainsString('RuntimeException', $response->getContent());
        $this->assertStringNotContainsString(__FILE__, $response->getContent());
    }
}
