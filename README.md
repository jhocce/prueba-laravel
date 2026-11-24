# Biblioteca API

API REST para gestionar una biblioteca de libros con Laravel 13 y PostgreSQL 17, ejecutada con Docker Compose.

Incluye CRUD completo de libros, filtrado por categoría, gestión mínima de categorías, validaciones con Form Requests, respuestas JSON con API Resources y pruebas de integración.

## Stack

| Componente | Versión |
|---|---|
| PHP | 8.5.13 |
| Laravel | 13.44.1 |
| PostgreSQL | 17.12 (contenedor) |
| Composer | 2.11.2 (contenedor) |
| PHPUnit | 12.5.45 |
| Scramble | 0.13.55 (OpenAPI 3.1) |
| Docker Compose | v2+ |

> No es necesario tener PHP, Composer ni PostgreSQL instalados en el host: todo se ejecuta en contenedores.

## Inicio rápido

Requisito: Docker Desktop en ejecución.

```bash
docker compose up -d --build
```

La imagen se construye con el código, `vendor` y todas las dependencias (incluido Scramble). Al arrancar, el contenedor:

1. Crea `.env` a partir de `.env.example` si no existe.
2. Genera `APP_KEY` si falta.
3. Ejecuta `php artisan package:discover --ansi`.
4. Ejecuta `php artisan migrate --force`.
5. Ejecuta los seeders (idempotentes).
6. Levanta el servidor en `http://localhost:8000`.

> La imagen es autocontenida: no se monta el código del host. Si cambias código, reconstruye con `docker compose up -d --build`. La primera construcción descarga las dependencias; los arranques posteriores no ejecutan Composer.

Verificar que está en marcha:

```bash
curl http://localhost:8000/up
```

La API queda disponible en `http://localhost:8000` (sin prefijo `/api`, tal como pide el enunciado).

Documentación interactiva: [http://localhost:8000/docs/api](http://localhost:8000/docs/api).

Para reiniciar la base de datos con los datos de demostración:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Para detener los servicios:

```bash
docker compose down
```

Para eliminar también el volumen de PostgreSQL (borra todos los datos):

```bash
docker compose down -v
```

## Endpoints

| Método | Ruta | Descripción | Éxito |
|---|---|---|---|
| `GET` | `/books` | Listar todos los libros | `200` |
| `GET` | `/books?category={id}` | Filtrar libros por categoría | `200` |
| `GET` | `/books/{id}` | Ver un libro (extra) | `200` |
| `POST` | `/books` | Crear un libro | `201` |
| `PUT` | `/books/{id}` | Actualizar un libro completo | `200` |
| `DELETE` | `/books/{id}` | Eliminar un libro | `204` |
| `GET` | `/categories` | Listar categorías | `200` |
| `POST` | `/categories` | Crear categoría | `201` |
| `DELETE` | `/categories/{id}` | Eliminar categoría sin libros | `204` |

### Códigos de error

| Código | Situación |
|---|---|
| `404` | Libro o categoría inexistente. |
| `409` | Se intenta eliminar una categoría con libros asociados. |
| `422` | Validación fallida o filtro con categoría inválida/inexistente. |

Las respuestas exitosas usan la envoltura `data`. Los errores de validación siguen el formato estándar de Laravel:

```json
{
  "message": "El campo título debe tener al menos 3 caracteres.",
  "errors": {
    "title": ["El campo título debe tener al menos 3 caracteres."]
  }
}
```

## Documentación OpenAPI (Swagger)

La API genera documentación **OpenAPI 3.1** automáticamente con [Scramble](https://scramble.dedoc.co/), a partir de las rutas, los Form Requests y los API Resources. No requiere anotaciones manuales.

| Recurso | URL |
|---|---|
| UI interactiva | `http://localhost:8000/docs/api` |
| Especificación JSON | `http://localhost:8000/docs/api.json` |

- La especificación incluye `/books`, `/books/{book}`, `/categories` y `/categories/{category}`, con parámetros, esquemas de request/response y respuestas `200`, `201`, `204`, `404`, `409` y `422` cuando corresponden.
- El JSON es compatible con Swagger UI, Postman e Insomnia: se puede importar desde `/docs/api.json`.
- Configuración: [config/scramble.php](config/scramble.php). Se ajustó `api_path` para documentar rutas sin prefijo `/api`.
- La documentación solo es accesible en los entornos `local` y `testing` (middleware `RestrictedDocsAccess` + Gate `viewApiDocs`). En producción responde `403` hasta habilitarla explícitamente.
- La UI carga los assets de Stoplight Elements desde unpkg; sin acceso a internet, usa la especificación JSON y un cliente como Postman o Swagger UI.
- La UI usa el layout `stacked` de Stoplight Elements: los endpoints aparecen uno debajo de otro, sin menú lateral oculto.
- Si cambias rutas o validaciones y la documentación no se actualiza:

  ```bash
  docker compose exec app php artisan scramble:clear
  ```

## Ejemplos

Listar libros:

```bash
curl -s http://localhost:8000/books -H "Accept: application/json"
```

Filtrar por categoría:

```bash
curl -s "http://localhost:8000/books?category=1" -H "Accept: application/json"
```

Crear un libro:

```bash
curl -s -X POST http://localhost:8000/books \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title":"El nombre del viento","author":"Patrick Rothfuss","description":"Primera parte.","published_year":2007,"category_id":1}'
```

Actualizar un libro:

```bash
curl -s -X PUT http://localhost:8000/books/16 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title":"El nombre del viento (revisado)","author":"Patrick Rothfuss","description":"Actualizado.","published_year":2007,"category_id":1}'
```

Eliminar un libro:

```bash
curl -s -i -X DELETE http://localhost:8000/books/16 -H "Accept: application/json"
```

Crear una categoría:

```bash
curl -s -X POST http://localhost:8000/categories \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Poesía"}'
```

> En Windows PowerShell el paso de JSON inline con `curl.exe` puede mangling de comillas. Usa `docs/api.http` con la extensión REST Client de VS Code, o guarda el JSON en un archivo y envía `--data-binary "@archivo.json"`.

## Pruebas

La suite usa PostgreSQL sobre la base `biblioteca_test` (creada automáticamente por el contenedor de base de datos). `phpunit.xml` fuerza el entorno `testing` y la base de test para no tocar la base de desarrollo.

```bash
docker compose exec app php artisan test
```

Resultado actual:

```text
Tests: 36 passed (158 assertions)
```

También se puede ejecutar Pint:

```bash
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/pint --test
```

## Estructura

```text
app/
├── Http/
│   ├── Controllers/       BookController, CategoryController
│   ├── Middleware/        ForceJsonResponse
│   ├── Requests/          StoreBookRequest, UpdateBookRequest, IndexBookRequest, StoreCategoryRequest
│   └── Resources/         BookResource, CategoryResource
└── Models/                Book, Category

database/
├── factories/             BookFactory, CategoryFactory
├── migrations/            categories, books (+ migraciones por defecto de Laravel)
└── seeders/               CategorySeeder, BookSeeder, DatabaseSeeder

docker/
├── entrypoint.sh
└── postgres/init/         01-create-test-database.sql

docs/
└── api.http               Colección de peticiones para VS Code REST Client

tests/Feature/             BookApiTest, CategoryApiTest, ApiDocumentationTest
config/scramble.php        Configuración de OpenAPI
Dockerfile
docker-compose.yml
phpunit.xml
```

## Base de datos

PostgreSQL se ejecuta en el servicio `db` con estos valores de desarrollo:

| Variable | Valor |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `db` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `biblioteca` |
| `DB_USERNAME` | `biblioteca` |
| `DB_PASSWORD` | `secret` |

La base `biblioteca_test` se crea con el script `docker/postgres/init/01-create-test-database.sql` al inicializar el volumen.

El puerto de PostgreSQL no se publica al host; solo es accesible desde la red interna de Docker Compose. Para inspeccionar la base:

```bash
docker compose exec db psql -U biblioteca -d biblioteca
```

## Decisiones de la API

- Los endpoints se publican **sin prefijo `/api`** para respetar literalmente el enunciado. Si se desea el prefijo, cambiar en `bootstrap/app.php`:

  ```php
  apiPrefix: 'api',
  ```

  y ejecutar `php artisan route:clear`. Las rutas pasarían a `/api/books`, etc.

- `PUT /books/{id}` es un reemplazo completo: todos los campos obligatorios deben enviarse también al editar.
- Filtrar por una categoría inexistente devuelve `422` (parámetro inválido), no una lista vacía.
- Eliminar una categoría con libros devuelve `409 Conflict`.
- Se añadieron endpoints de categorías (`GET`, `POST`, `DELETE`) porque el enunciado exige validar la unicidad del nombre y evitar el borrado de categorías con libros.
- Se conservan las tablas por defecto de Laravel (`users`, `cache`, `jobs`) porque el esqueleto las usa para sesiones, caché y colas; no forman parte del dominio.
- No hay autenticación ni interfaz web: el alcance es la API JSON.

## Versiones mínimas de seguridad

| Herramienta | Mínimo | Versión en el proyecto |
|---|---|---|
| Composer | 2.11.2 | 2.11.2 |
| PHPUnit | 12.5.22 | 12.5.45 |
| PostgreSQL | 17.x | 17.12 |

Composer 2.11.2 incorpora la corrección de `symfony/filesystem` (GHSA-6prc-h956-hxjq); PHPUnit 12.5.22 corrige CVE-2026-41570. Las dependencias están fijadas en `composer.lock`.

## Aviso del escáner de Docker

> **Advertencia:** el escáner de la extensión Docker de VS Code reporta **6 vulnerabilidades de severidad alta** en la imagen base oficial `php:8.5.13-cli`.
>
> El aviso está asociado a la imagen base (paquetes del sistema operativo Debian incluidos en ella), no a una detección sobre el código de esta aplicación ni sobre sus dependencias de Composer. La aplicación usa las versiones parcheadas de las herramientas: PHP 8.5.13, Composer 2.11.2, PHPUnit 12.5.45 y PostgreSQL 17.12.
>
> Para un entorno productivo:
>
> 1. Actualizar la imagen base cuando exista un tag parcheado (`php:8.5.x-cli`).
> 2. Reconstruir con `docker compose build --pull` y volver a ejecutar el escáner.
> 3. Opcionalmente, añadir un `apt-get upgrade -y` en el Dockerfile o usar una imagen base endurecida.
>
> En el contexto de esta evaluación local el aviso no bloquea la ejecución, pero debe revisarse antes de desplegar la aplicación.

## Entorno versionado (excepción consciente)

> **Decisión del desarrollador:** este repositorio versiona `.env` de forma intencional para dejar registrada la configuración de desarrollo y facilitar la ejecución y evaluación del proyecto. Reconozco que no es una buena práctica; en un proyecto real, `.env` debe excluirse.

- Solo contiene valores locales de desarrollo (`APP_KEY` de desarrollo, `DB_PASSWORD=secret`).
- No contiene secretos de producción.
- El contenedor no usa este archivo directamente: su entrypoint crea un `.env` interno desde `.env.example` y genera su propia `APP_KEY`.
- Si el repositorio se hace público o se despliega en producción, elimina `.env` del historial y rota las credenciales.

### Archivos que no se versionan

| Archivo / patrón | Motivo |
|---|---|
| `vendor/`, `node_modules/` | Dependencias instaladas por Docker/Composer; la imagen las construye. |
| `.env.backup`, `.env.production`, `.env.local` | Entornos alternativos; no deben subirse. |
| `docker-compose.override.yml` | Overrides locales (credenciales/volúmenes); no se suben. |
| `bootstrap/cache/*.php` | Caché generada por Laravel. |
| `storage/logs/*`, `storage/framework/{cache,sessions,views}/*` | Archivos de ejecución. |
| `.phpunit.result.cache` | Caché de pruebas. |
| `public/build`, `public/hot`, `public/storage` | Artefactos generados. |
| `database/*.sqlite` | Base local no usada (PostgreSQL). |
| `.idea/`, `.vscode/`, `.DS_Store`, `*.log` | IDE/SO. |
| `CLAUDE.md`, `AGENTS.md` | Guías generadas por el scaffold de Laravel Boost; no forman parte de la aplicación. |

> La excepción es únicamente `.env`: el resto de archivos de ejecución se excluye. `composer.lock` **sí** se versiona para reproducibilidad. La imagen Docker se construye desde el código y no requiere `vendor/` en el repositorio. Los datos de PostgreSQL viven en el volumen Docker, fuera del repositorio.

## Comandos útiles

```bash
docker compose up -d --build          # levantar todo
docker compose logs -f app            # logs de la aplicación
docker compose exec app php artisan route:list
docker compose exec app php artisan tinker
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose down                    # detener
docker compose down -v                 # detener y borrar el volumen de PostgreSQL
```

## Documentación adicional

- `http://localhost:8000/docs/api` — documentación interactiva OpenAPI/Swagger.
- `http://localhost:8000/docs/api.json` — especificación OpenAPI 3.1.
- [PLAN_DESARROLLO.md](PLAN_DESARROLLO.md) — plan, decisiones, matriz de pruebas y changelog.
- [docs/api.http](docs/api.http) — colección de peticiones para VS Code REST Client.
