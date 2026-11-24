<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Libro de prueba',
            'author' => 'Autor de prueba',
            'description' => 'Descripción de prueba.',
            'published_year' => 2020,
            'category_id' => Category::factory()->create()->id,
        ], $overrides);
    }

    public function test_lista_todos_los_libros(): void
    {
        $category = Category::factory()->create(['name' => 'Novela']);
        Book::factory()->count(3)->create(['category_id' => $category->id]);

        $this->getJson('/books')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'description',
                        'published_year',
                        'category_id',
                        'category' => ['id', 'name'],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_lista_vacia_cuando_no_hay_libros(): void
    {
        $this->getJson('/books')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_filtra_libros_por_categoria(): void
    {
        $novela = Category::factory()->create();
        $historia = Category::factory()->create();
        Book::factory()->count(2)->create(['category_id' => $novela->id]);
        Book::factory()->create(['category_id' => $historia->id]);

        $response = $this->getJson('/books?category='.$novela->id);

        $response->assertOk()->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $book) {
            $this->assertSame($novela->id, $book['category_id']);
        }
    }

    public function test_filtro_categoria_sin_libros_devuelve_lista_vacia(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/books?category='.$category->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_filtro_categoria_inexistente_devuelve_422(): void
    {
        $this->getJson('/books?category=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_filtro_categoria_no_numerica_devuelve_422(): void
    {
        $this->getJson('/books?category=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_crea_un_libro_valido(): void
    {
        $category = Category::factory()->create();
        $payload = [
            'title' => 'El nombre del viento',
            'author' => 'Patrick Rothfuss',
            'description' => 'Una novela de fantasía.',
            'published_year' => 2007,
            'category_id' => $category->id,
        ];

        $this->postJson('/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', 'El nombre del viento')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertHeader('Location');

        $this->assertDatabaseHas('books', [
            'title' => 'El nombre del viento',
            'author' => 'Patrick Rothfuss',
            'category_id' => $category->id,
        ]);
    }

    public function test_crea_libro_con_descripcion_nula(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/books', [
            'title' => 'Libro sin descripción',
            'author' => 'Autor',
            'published_year' => 2020,
            'category_id' => $category->id,
        ])->assertCreated()->assertJsonPath('data.description', null);
    }

    public function test_titulo_es_obligatorio(): void
    {
        $this->postJson('/books', $this->payload(['title' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_titulo_debe_tener_al_menos_tres_caracteres(): void
    {
        $this->postJson('/books', $this->payload(['title' => 'AB']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_autor_es_obligatorio(): void
    {
        $this->postJson('/books', $this->payload(['author' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['author']);
    }

    public function test_ano_de_publicacion_es_obligatorio(): void
    {
        $payload = $this->payload();
        unset($payload['published_year']);

        $this->postJson('/books', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['published_year']);
    }

    public function test_ano_de_publicacion_debe_ser_entero(): void
    {
        $this->postJson('/books', $this->payload(['published_year' => 'dos mil']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['published_year']);
    }

    public function test_ano_de_publicacion_no_puede_ser_futuro(): void
    {
        $this->postJson('/books', $this->payload(['published_year' => now()->year + 1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['published_year']);
    }

    public function test_categoria_es_obligatoria(): void
    {
        $payload = $this->payload();
        unset($payload['category_id']);

        $this->postJson('/books', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_categoria_debe_existir(): void
    {
        $this->postJson('/books', $this->payload(['category_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_creacion_sin_campos_requeridos_devuelve_errores(): void
    {
        $this->postJson('/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'published_year',
                'category_id',
            ]);
    }

    public function test_actualiza_un_libro(): void
    {
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);
        $newCategory = Category::factory()->create();

        $this->putJson('/books/'.$book->id, [
            'title' => 'Título actualizado',
            'author' => 'Autor actualizado',
            'description' => 'Descripción actualizada.',
            'published_year' => 2021,
            'category_id' => $newCategory->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título actualizado')
            ->assertJsonPath('data.category.id', $newCategory->id);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Título actualizado',
            'category_id' => $newCategory->id,
        ]);
    }

    public function test_actualizar_libro_inexistente_devuelve_404(): void
    {
        $this->putJson('/books/999', $this->payload())
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    public function test_actualizacion_invalida_no_modifica_el_libro(): void
    {
        $book = Book::factory()->create();

        $this->putJson('/books/'.$book->id, $this->payload(['title' => 'AB']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }

    public function test_actualizar_sin_categoria_devuelve_422(): void
    {
        $book = Book::factory()->create();
        $payload = $this->payload();
        unset($payload['category_id']);

        $this->putJson('/books/'.$book->id, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_elimina_un_libro(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson('/books/'.$book->id)->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_eliminar_libro_inexistente_devuelve_404(): void
    {
        $this->deleteJson('/books/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    public function test_muestra_un_libro(): void
    {
        $book = Book::factory()->create();

        $this->getJson('/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);
    }

    public function test_mostrar_libro_inexistente_devuelve_404(): void
    {
        $this->getJson('/books/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }
}
