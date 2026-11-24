<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
            [
                'title' => 'Cien años de soledad',
                'author' => 'Gabriel García Márquez',
                'description' => 'La historia de la familia Buendía en Macondo.',
                'published_year' => 1967,
                'category' => 'Novela',
            ],
            [
                'title' => 'Rayuela',
                'author' => 'Julio Cortázar',
                'description' => 'Una novela que puede leerse en múltiples órdenes.',
                'published_year' => 1963,
                'category' => 'Novela',
            ],
            [
                'title' => 'La casa de los espíritus',
                'author' => 'Isabel Allende',
                'description' => null,
                'published_year' => 1982,
                'category' => 'Novela',
            ],
            [
                'title' => 'Dune',
                'author' => 'Frank Herbert',
                'description' => 'Política, religión y ecología en el planeta Arrakis.',
                'published_year' => 1965,
                'category' => 'Ciencia Ficción',
            ],
            [
                'title' => 'Fundación',
                'author' => 'Isaac Asimov',
                'description' => 'El declive del Imperio Galáctico y la psicohistoria.',
                'published_year' => 1951,
                'category' => 'Ciencia Ficción',
            ],
            [
                'title' => 'Neuromante',
                'author' => 'William Gibson',
                'description' => null,
                'published_year' => 1984,
                'category' => 'Ciencia Ficción',
            ],
            [
                'title' => 'Sapiens',
                'author' => 'Yuval Noah Harari',
                'description' => 'Una breve historia de la humanidad.',
                'published_year' => 2011,
                'category' => 'Historia',
            ],
            [
                'title' => 'Armas, gérmenes y acero',
                'author' => 'Jared Diamond',
                'description' => 'Cómo las sociedades humanas llegaron a dominar el planeta.',
                'published_year' => 1997,
                'category' => 'Historia',
            ],
            [
                'title' => '1491',
                'author' => 'Charles C. Mann',
                'description' => 'La historia de las Américas antes de Colón.',
                'published_year' => 2005,
                'category' => 'Historia',
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'description' => 'Buenas prácticas para escribir código legible.',
                'published_year' => 2008,
                'category' => 'Tecnología',
            ],
            [
                'title' => 'El programador pragmático',
                'author' => 'Andrew Hunt y David Thomas',
                'description' => 'Consejos prácticos para el desarrollo de software.',
                'published_year' => 1999,
                'category' => 'Tecnología',
            ],
            [
                'title' => 'Refactoring',
                'author' => 'Martin Fowler',
                'description' => null,
                'published_year' => 1999,
                'category' => 'Tecnología',
            ],
            [
                'title' => 'El Principito',
                'author' => 'Antoine de Saint-Exupéry',
                'description' => 'Un clásico de la literatura infantil.',
                'published_year' => 1943,
                'category' => 'Infantil',
            ],
            [
                'title' => 'Matilda',
                'author' => 'Roald Dahl',
                'description' => 'La historia de una niña extraordinaria.',
                'published_year' => 1988,
                'category' => 'Infantil',
            ],
            [
                'title' => 'Harry Potter y la piedra filosofal',
                'author' => 'J. K. Rowling',
                'description' => null,
                'published_year' => 1997,
                'category' => 'Infantil',
            ],
        ];

        foreach ($books as $book) {
            $category = Category::where('name', $book['category'])->firstOrFail();

            Book::firstOrCreate(
                [
                    'title' => $book['title'],
                    'author' => $book['author'],
                ],
                [
                    'description' => $book['description'],
                    'published_year' => $book['published_year'],
                    'category_id' => $category->id,
                ],
            );
        }
    }
}
