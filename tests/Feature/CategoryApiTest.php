<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_las_categorias(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/categories')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_crea_una_categoria_valida(): void
    {
        $this->postJson('/categories', ['name' => 'Poesía'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Poesía');

        $this->assertDatabaseHas('categories', ['name' => 'Poesía']);
    }

    public function test_nombre_de_categoria_es_obligatorio(): void
    {
        $this->postJson('/categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_nombre_de_categoria_debe_ser_unico(): void
    {
        Category::factory()->create(['name' => 'Poesía']);

        $this->postJson('/categories', ['name' => 'Poesía'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_elimina_una_categoria_sin_libros(): void
    {
        $category = Category::factory()->create();

        $this->deleteJson('/categories/'.$category->id)->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_no_elimina_una_categoria_con_libros(): void
    {
        $category = Category::factory()->create();
        Book::factory()->create(['category_id' => $category->id]);

        $this->deleteJson('/categories/'.$category->id)
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'No se puede eliminar la categoría porque tiene libros asociados.',
            );

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_eliminar_categoria_inexistente_devuelve_404(): void
    {
        $this->deleteJson('/categories/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }
}
