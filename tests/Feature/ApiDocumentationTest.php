<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_la_interfaz_de_documentacion_esta_disponible(): void
    {
        $this->get('/docs/api')->assertOk();
    }

    public function test_el_documento_openapi_describe_los_endpoints(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk();

        $this->assertStringStartsWith('3.1', $response->json('openapi'));
        $this->assertSame('1.0.0', $response->json('info.version'));

        $paths = $response->json('paths');

        $this->assertArrayHasKey('/books', $paths);
        $this->assertArrayHasKey('/books/{book}', $paths);
        $this->assertArrayHasKey('/categories', $paths);
        $this->assertArrayHasKey('/categories/{category}', $paths);

        $this->assertArrayHasKey('get', $paths['/books']);
        $this->assertArrayHasKey('post', $paths['/books']);
        $this->assertArrayHasKey('put', $paths['/books/{book}']);
        $this->assertArrayHasKey('delete', $paths['/books/{book}']);
    }
}
