# Plan de Desarrollo — Biblioteca API

| | |
|---|---|
| **Proyecto** | API REST de gestión de una biblioteca de libros |
| **Framework** | Laravel 13.44.1 |
| **Runtime** | Docker Compose: PHP 8.5.13 + PostgreSQL 17.12 |
| **Documento** | Versión 1.3.0 — 24/11/2026 |
| **Estado** | Implementado y verificado; repositorio Git pendiente de confirmación |
| **Directorio** | `d:\pruebaphp` |

**Resumen ejecutivo.** Se construyó una API REST con Laravel 13 que permite listar, crear, editar y eliminar libros, filtrarlos por categoría y gestionar el alta y la baja de categorías. La aplicación se ejecuta con Docker Compose (PHP 8.5 + PostgreSQL 17) y expone los endpoints exactos del enunciado, sin prefijo `/api`. La solución usa migraciones, seeders, modelos Eloquent con relaciones, Form Requests, API Resources, middleware para forzar JSON y pruebas de integración con PHPUnit. Resultado actual: **36 pruebas y 158 aserciones en verde**.

---

## 1. Enunciado

### 1.1 Descripción

Desarrollar una pequeña aplicación funcional para gestionar una biblioteca de libros. La solución debe permitir realizar las operaciones esenciales de gestión (CRUD) y filtrado de contenido mediante el framework Laravel y PHP.

### 1.2 Funcionalidades requeridas

1. Listar los libros disponibles en el sistema.
2. Crear un nuevo libro relacionándolo a una categoría.
3. Editar la información de un libro existente.
4. Eliminar libros registrados.
5. Filtrar la lista de libros según su categoría.

### 1.3 Requisitos de modelo y base de datos

Crear la base de datos con dos tablas principales y sus respectivas relaciones Eloquent:

**Tabla `books` (Libros)**

| Campo | Tipo | Restricciones |
|---|---|---|
| `id` | BigInt / auto-incremental | PK |
| `title` | String | Requerido |
| `author` | String | Requerido |
| `description` | Text | Opcional |
| `published_year` | Year / Int | Requerido |
| `category_id` | BigInt | FK → `categories.id` |

**Tabla `categories` (Categorías)**

| Campo | Tipo | Restricciones |
|---|---|---|
| `id` | BigInt / auto-incremental | PK |
| `name` | String | Requerido y único |

### 1.4 Rutas y controladores

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/books` | Listar todos los libros almacenados |
| `POST` | `/books` | Crear un nuevo libro |
| `PUT` | `/books/{id}` | Actualizar/editar la información de un libro |
| `DELETE` | `/books/{id}` | Eliminar un libro específico |
| `GET` | `/books?category={category_id}` | Filtrar la lista de libros por categoría |

### 1.5 Validaciones

- `title`: obligatorio, mínimo 3 caracteres.
- `author`: obligatorio.
- `published_year`: obligatorio, año válido.
- `category_id`: obligatorio, debe existir en `categories`.
- Categorías: el nombre debe ser único al registrar una nueva categoría.

### 1.6 Lógica de negocio

- Verificar la existencia del libro antes de proceder con su eliminación.
- Verificar que una categoría no tenga libros asociados antes de permitir su eliminación.

### 1.7 Criterios de evaluación

- Arquitectura de código (MVC).
- Uso de migraciones y seeders.
- Form Requests para validación.
- Manejo adecuado de ORM Eloquent.
- Tratamiento de respuestas y errores HTTP.

---

## 2. Alcance y decisiones

### 2.1 Objetivo

Entregar una aplicación Laravel funcional y reproducible con Docker Compose, con CRUD completo de libros, filtrado por categoría, gestión mínima de categorías, validaciones centralizadas, pruebas automatizadas y documentación de uso.

### 2.2 Dentro del alcance

- CRUD de libros: listar, crear, editar (PUT) y eliminar.
- Filtro de libros por categoría mediante query string.
- Endpoints de soporte para categorías: listar, crear y eliminar.
- Migraciones, modelos Eloquent, factories y seeders.
- Form Requests, API Resources, controladores y rutas.
- Middleware `ForceJsonResponse` y mensajes de error en español.
- Docker Compose con PHP 8.5 y PostgreSQL 17.
- Pruebas feature contra PostgreSQL (`biblioteca_test`).
- README, colección `docs/api.http` y este plan con changelog.

### 2.3 Fuera del alcance

- Autenticación y autorización.
- Interfaz web/SPA (la entrega es una API JSON).
- Paginación, búsqueda por texto y ordenamiento configurable.
- Soft deletes, auditoría y carga de archivos.
- Despliegue en producción, CI/CD y endurecimiento del contenedor.
- Internacionalización completa (solo se traducen los mensajes de validación).

### 2.4 Supuestos

1. El cliente consume JSON; la API fuerza `Accept: application/json` en el grupo de rutas de API.
2. Los endpoints se publican sin prefijo `/api` para respetar literalmente el enunciado. El cambio a `/api` es una línea en `bootstrap/app.php`.
3. `PUT /books/{id}` reemplaza el recurso completo: todos los campos obligatorios deben enviarse también en la edición.
4. La gestión de categorías es necesaria para demostrar la unicidad del nombre y la regla de no borrar categorías con libros.
5. Un año de publicación válido está entre 1000 y el año actual inclusive (decisión D18).
6. La aplicación es de un solo usuario y no requiere autenticación.
7. PostgreSQL corre en el contenedor `db`; no se publica al host.
8. Docker Desktop es el único requisito de ejecución en el host.

### 2.5 Decisiones de diseño

| ID | Decisión | Alternativa considerada | Justificación |
|---|---|---|---|
| D1 | Laravel 13 + PHP 8.5 | Laravel 12 + PHP 8.2 | Versión estable actual; PHP 8.5 está dentro del rango soportado (8.3–8.5). |
| D2 | API JSON con `routes/api.php` y `apiPrefix: ''` | Rutas en `routes/web.php` | Mantiene el middleware de API y las rutas exactas `/books` del enunciado. |
| D3 | Docker Compose + PostgreSQL 17 | SQLite local / MySQL | Entorno reproducible y aislado; PostgreSQL en contenedor con volumen persistente. |
| D4 | Controladores delgados + Form Requests + API Resources | Lógica y validación dentro del controlador | Cumple MVC y separa validación, negocio y presentación. |
| D5 | Route model binding (`{book}`, `{category}`) | Buscar con `findOrFail` en el controlador | Laravel devuelve 404 automáticamente si el recurso no existe. |
| D6 | `PUT` = reemplazo completo | `PATCH` parcial | El enunciado pide `PUT`; se exigen todos los campos obligatorios. |
| D7 | Filtro por categoría inexistente → `422` | `200` con lista vacía | Un identificador fuera del catálogo es un parámetro inválido. |
| D8 | Eliminar categoría con libros → `409 Conflict` | `422` / `400` | Representa correctamente un conflicto con el estado actual. |
| D9 | `Category hasMany Book` / `Book belongsTo Category` | Tabla intermedia | Un libro pertenece a una sola categoría según el modelo pedido. |
| D10 | Respuestas con envoltura `data` | Arrays/objetos planos | Consistencia y uso idiomático de API Resources. |
| D11 | Sin autenticación | Sanctum | No forma parte del enunciado. |
| D12 | PHPUnit 12.5.22+ contra PostgreSQL `biblioteca_test` | SQLite en memoria | Pruebas fieles al motor real, aisladas de la base de desarrollo. |
| D13 | Mensajes de validación en español | Mensajes por defecto en inglés | Mejor experiencia para el evaluador hispanohablante. |
| D14 | Sin paginación | `paginate()` | El enunciado pide listar todos los libros. |
| D15 | Endpoints de categorías de soporte (`GET`, `POST`, `DELETE`) | Solo endpoints de libros | Permiten demostrar la unicidad y la regla de borrado de categorías. |
| D16 | Conservar migraciones por defecto de Laravel (`users`, `cache`, `jobs`) | Esquema mínimo | El esqueleto las usa para sesiones, caché y colas; se documentan en el README. |
| D17 | `description` limitada a 5000 caracteres | Sin límite | Evita cargas desproporcionadas; el enunciado la define como opcional. |
| D18 | `published_year` entre 1000 y el año actual | Solo `integer` | “Año válido” se interpreta como año histórico plausible y no futuro. |
| D19 | Dockerfile + entrypoint idempotente | `docker compose run` manual | `docker compose up` deja la aplicación lista: `.env`, clave, dependencias, migraciones y seeders. |
| D20 | `APP_DEBUG=false` por defecto | Debug local siempre activo | Respuestas de error limpias para la evaluación; se puede activar para depurar. |
| D21 | Imagen autocontenida (código + `vendor` dentro de la imagen) | Bind mount del código + Composer en runtime | El usuario solo ejecuta `docker compose up -d --build` y abre `/docs/api`; no se instalan dependencias al arrancar. |

---

## 3. Stack y entorno Docker

### 3.1 Versiones verificadas

| Componente | Versión | Notas |
|---|---|---|
| PHP | 8.5.13 | Imagen `php:8.5.13-cli`, extensiones `pdo_pgsql`, `pgsql`, `mbstring`. |
| Laravel | 13.44.1 | `laravel/laravel` 13.11.0 como esqueleto. |
| PostgreSQL | 17.12 | Imagen `postgres:17.12`; base `biblioteca` y base de test `biblioteca_test`. |
| Composer | 2.11.2 | Copiado desde `composer:2.11.2`; cumple el mínimo de seguridad. |
| PHPUnit | 12.5.45 | Dependencia de desarrollo; cumple el mínimo 12.5.22. |
| Scramble | 0.13.55 | Genera OpenAPI 3.1 y la UI de documentación. |
| Docker | 29.9.3 | Docker Desktop en Windows. |
| Docker Compose | v5.7.1 | `docker compose`. |

> Laravel 13 soporta oficialmente PHP 8.3–8.5. PHP 8.6 no se usa. El proyecto no fija PHP 8.5.13 en `composer.json`; la versión vive en la imagen Docker.

### 3.2 Servicios

| Servicio | Imagen / build | Puerto host | Persistencia |
|---|---|---|---|
| `app` | `Dockerfile` sobre `php:8.5.13-cli` | `8000:8000` | Código y `vendor` dentro de la imagen; sin bind mount. |
| `db` | `postgres:17.12` | No publicado | Volumen `postgres_data`. |

`docker-compose.yml` define:

- `app` con `depends_on: db: condition: service_healthy`.
- `db` con healthcheck `pg_isready`.
- Variables de conexión en `.env` (el compose no las duplica para no pisar el entorno de pruebas).
- Script `docker/postgres/init/01-create-test-database.sql` que crea `biblioteca_test` al inicializar el volumen.

### 3.3 Inicio rápido

```bash
docker compose up -d --build
```

La imagen incluye el código, `vendor` y todas las dependencias (incluido Scramble). El entrypoint `docker/entrypoint.sh`:

1. Copia `.env.example` a `.env` si no existe.
2. Genera `APP_KEY` si falta.
3. Ejecuta `php artisan package:discover --ansi`.
4. Ejecuta `php artisan migrate --force`.
5. Ejecuta `php artisan db:seed --force` (seeders idempotentes).
6. Inicia `php artisan serve --host=0.0.0.0 --port=8000`.

> La imagen es autocontenida: no hay bind mount del código y no se ejecuta Composer al arrancar. Tras cambiar código, reconstruir con `docker compose up -d --build`.

Verificación:

```bash
curl http://localhost:8000/up
```

Reset de la base de desarrollo:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### 3.4 Conexión a PostgreSQL

| Variable | Valor |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `db` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `biblioteca` |
| `DB_USERNAME` | `biblioteca` |
| `DB_PASSWORD` | `secret` |

La base de pruebas (`biblioteca_test`) se configura en `phpunit.xml` y se fuerza con `force="true"` para que las variables del contenedor no la sobrescriban.

### 3.5 Pruebas y base de datos de test

- `APP_ENV=testing`, `APP_DEBUG=false`, `DB_DATABASE=biblioteca_test` se fuerzan en `phpunit.xml`.
- El compose de `app` no define `DB_*` para evitar que las variables de entorno pisen la configuración de PHPUnit.
- `RefreshDatabase` migra la base de test en cada ejecución.

### 3.6 Versiones mínimas por seguridad

| Herramienta | Mínimo | Verificado | Motivo |
|---|---|---|---|
| Composer | 2.11.2 | 2.11.2 | Corrige `symfony/filesystem` (GHSA-6prc-h956-hxjq); ramas anteriores ya requerían 2.2.30/2.10.3 por CVE-2026-59944 y 2.2.27/2.9.6 por Perforce. |
| PHPUnit | 12.5.22 | 12.5.45 | Corrige CVE-2026-41570 (inyección de metacaracteres INI). |
| PostgreSQL | 17.x | 17.12 | Versión estable y parcheada de la rama 17. |
| SQLite | 3.54.0 si se usa | No se usa | La aplicación y las pruebas usan PostgreSQL, por lo que se elimina el riesgo residual de las CVEs de SQLite. |

Comprobaciones:

```bash
docker compose exec app composer --version --no-ansi
docker compose exec app php vendor/phpunit/phpunit/phpunit --version
docker compose exec db psql -U biblioteca -d biblioteca -c "select version();"
```

### 3.7 Cambios opcionales

- **Prefijo `/api`:** cambiar `apiPrefix: ''` por `apiPrefix: 'api'` en `bootstrap/app.php` y ejecutar `php artisan route:clear`.
- **Otro motor de base de datos:** las migraciones son portables; ajustar `.env` y el servicio `db`. Para MySQL habría que añadir `pdo_mysql` a la imagen; para SQLite, usar un archivo y verificar SQLite ≥ 3.54.0.
- **Debug:** poner `APP_DEBUG=true` en `.env` y reiniciar el contenedor.

---

## 4. Arquitectura

### 4.1 Flujo de una petición

```text
Cliente HTTP
   │  Accept: application/json
   ▼
routes/api.php  (apiPrefix: '' → /books, /categories)
   ▼
ForceJsonResponse (middleware del grupo api)
   ▼
Form Request (StoreBookRequest / UpdateBookRequest / IndexBookRequest / StoreCategoryRequest)
   ├── validación fallida ──► 422 JSON { message, errors }
   ▼
Controlador (BookController / CategoryController)
   ├── Route model binding: recurso inexistente ──► 404
   ├── Regla de negocio: categoría con libros ──► 409
   ▼
Eloquent (Book::with('category'), Category::books())
   ▼
API Resource (BookResource / CategoryResource)
   ▼
Respuesta JSON: 200 / 201 / 204
```

### 4.2 Estructura

```text
d:\pruebaphp
├── app
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── BookController.php
│   │   │   └── CategoryController.php
│   │   ├── Middleware
│   │   │   └── ForceJsonResponse.php
│   │   ├── Requests
│   │   │   ├── StoreBookRequest.php
│   │   │   ├── UpdateBookRequest.php
│   │   │   ├── IndexBookRequest.php
│   │   │   └── StoreCategoryRequest.php
│   │   └── Resources
│   │       ├── BookResource.php
│   │       └── CategoryResource.php
│   └── Models
│       ├── Book.php
│       └── Category.php
├── bootstrap/app.php
├── database
│   ├── factories
│   ├── migrations
│   └── seeders
├── docker
│   ├── entrypoint.sh
│   └── postgres/init/01-create-test-database.sql
├── docs/api.http
├── lang/es/validation.php
├── routes/api.php
├── config/scramble.php
├── tests/Feature
│   ├── ApiDocumentationTest.php
│   ├── BookApiTest.php
│   └── CategoryApiTest.php
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── README.md
└── PLAN_DESARROLLO.md
```

### 4.3 Responsabilidades por capa

| Capa | Elemento | Responsabilidad |
|---|---|---|
| Entrada | `routes/api.php` | Declarar rutas y asociarlas a los controladores. |
| Formato | `ForceJsonResponse` | Forzar `Accept: application/json`. |
| Validación | Form Requests | Reglas y autorización de cada operación. |
| Negocio | Controladores | Orquestar Eloquent, aplicar 409 y elegir códigos HTTP. |
| Persistencia | Modelos Eloquent | Relaciones, `$fillable`, casts y consultas. |
| Presentación | API Resources | Forma estable del JSON de salida. |
| Datos | Migraciones / seeders / factories | Esquema, datos de demostración y datos de prueba. |
| Infraestructura | Dockerfile / compose / entrypoint | PHP, PostgreSQL, arranque y reproducibilidad. |

---

## 5. Modelo de datos

### 5.1 Tabla `categories`

| Columna | Tipo PostgreSQL | Restricciones |
|---|---|---|
| `id` | `bigint` (identity) | PK, auto-incremental. |
| `name` | `varchar(255)` | `NOT NULL`, `UNIQUE`. |
| `created_at`, `updated_at` | `timestamp` | Nullable. |

### 5.2 Tabla `books`

| Columna | Tipo PostgreSQL | Restricciones |
|---|---|---|
| `id` | `bigint` (identity) | PK, auto-incremental. |
| `title` | `varchar(255)` | `NOT NULL`. |
| `author` | `varchar(255)` | `NOT NULL`. |
| `description` | `text` | Nullable. |
| `published_year` | `smallint` | `NOT NULL`; validado entre 1000 y el año actual. |
| `category_id` | `bigint` | `NOT NULL`, FK → `categories.id`, `ON DELETE RESTRICT`. |
| `created_at`, `updated_at` | `timestamp` | Nullable. |

Migraciones clave:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->timestamps();
});

Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('author');
    $table->text('description')->nullable();
    $table->unsignedSmallInteger('published_year');
    $table->foreignId('category_id')->constrained()->restrictOnDelete();
    $table->timestamps();
});
```

### 5.3 Relaciones Eloquent

- `Category::books(): HasMany`
- `Book::category(): BelongsTo`
- `Book::$fillable`: `title`, `author`, `description`, `published_year`, `category_id`.
- Cast `published_year` a `integer`.

### 5.4 Integridad

- `categories.name` es único a nivel de base de datos; la validación `unique` añade el 422 amigable.
- `books.category_id` es obligatorio y debe existir.
- `ON DELETE RESTRICT` impide borrar una categoría con libros incluso si se salta la comprobación de la aplicación.
- No se permiten libros huérfanos.

### 5.5 Seeders

- `CategorySeeder`: 5 categorías (`Novela`, `Ciencia Ficción`, `Historia`, `Tecnología`, `Infantil`) con `firstOrCreate`.
- `BookSeeder`: 15 libros (3 por categoría) con `firstOrCreate`, algunas descripciones nulas.
- `DatabaseSeeder` llama a ambos; ejecutar `db:seed` varias veces no duplica registros.

---

## 6. Contrato de API

### 6.1 Convenciones

- **URL base local:** `http://localhost:8000`
- **Sin prefijo `/api`**, para coincidir con el enunciado.
- JSON en solicitud y respuesta.
- Los recursos se devuelven dentro de la clave `data`.
- Errores con formato estándar de Laravel: `{ "message": "...", "errors": { ... } }`.

### 6.2 Endpoints de libros

| Método | Ruta | Acción | Éxito | Errores |
|---|---|---|---|---|
| `GET` | `/books` | Listar todos | `200` | — |
| `GET` | `/books?category={id}` | Filtrar | `200` | `422` |
| `GET` | `/books/{id}` | Ver uno (extra) | `200` | `404` |
| `POST` | `/books` | Crear | `201` | `422` |
| `PUT` | `/books/{id}` | Actualizar completo | `200` | `404`, `422` |
| `DELETE` | `/books/{id}` | Eliminar | `204` | `404` |

### 6.3 Endpoints de categorías (soporte)

| Método | Ruta | Acción | Éxito | Errores |
|---|---|---|---|---|
| `GET` | `/categories` | Listar | `200` | — |
| `POST` | `/categories` | Crear con nombre único | `201` | `422` |
| `DELETE` | `/categories/{id}` | Eliminar sin libros | `204` | `404`, `409` |

### 6.4 Formato de respuestas

**Listado de libros — `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Cien años de soledad",
      "author": "Gabriel García Márquez",
      "description": "La historia de la familia Buendía en Macondo.",
      "published_year": 1967,
      "category_id": 1,
      "category": {
        "id": 1,
        "name": "Novela",
        "created_at": "2026-11-24T18:31:49.000000Z",
        "updated_at": "2026-11-24T18:31:49.000000Z"
      },
      "created_at": "2026-11-24T18:31:49.000000Z",
      "updated_at": "2026-11-24T18:31:49.000000Z"
    }
  ]
}
```

**Crear libro — `POST /books`**

```json
{
  "title": "Dune",
  "author": "Frank Herbert",
  "description": "Novela de ciencia ficción.",
  "published_year": 1965,
  "category_id": 2
}
```

Respuesta `201 Created` con el objeto en `data` y cabecera `Location`.

**Errores**

```json
// 404 Not Found
{ "message": "Recurso no encontrado." }

// 409 Conflict
{ "message": "No se puede eliminar la categoría porque tiene libros asociados." }

// 422 Unprocessable Content
{
  "message": "El campo título debe tener al menos 3 caracteres.",
  "errors": {
    "title": ["El campo título debe tener al menos 3 caracteres."]
  }
}
```

### 6.5 Códigos HTTP

| Código | Uso |
|---|---|
| `200` | Listado o actualización correcta. |
| `201` | Recurso creado. |
| `204` | Recurso eliminado, sin cuerpo. |
| `404` | Libro o categoría inexistente. |
| `409` | Categoría con libros asociados. |
| `422` | Validación fallida o filtro inválido. |

### 6.6 Semántica del filtro

- Sin `category`: todos los libros, ordenados por título.
- Categoría existente con libros: solo esos libros.
- Categoría existente sin libros: `200` con `data: []`.
- Categoría inexistente o no numérica: `422` con error en `category`.

---

### 6.7 Documentación OpenAPI

- UI interactiva: `http://localhost:8000/docs/api`.
- Especificación JSON: `http://localhost:8000/docs/api.json`.
- Generada automáticamente con Scramble 0.13.55 desde rutas, Form Requests y API Resources.
- `api_path` se configuró para incluir `books` y `categories` sin prefijo `/api`.
- Acceso restringido a `local`/`testing` mediante `RestrictedDocsAccess` y el Gate `viewApiDocs`.
- Para invalidar la caché del documento: `php artisan scramble:clear`.

---

## 7. Validaciones (Form Requests)

### 7.1 Libros (`StoreBookRequest` / `UpdateBookRequest`)

| Campo | Reglas |
|---|---|
| `title` | `required`, `string`, `min:3`, `max:255` |
| `author` | `required`, `string`, `max:255` |
| `description` | `nullable`, `string`, `max:5000` |
| `published_year` | `required`, `integer`, `min:1000`, `max:{año actual}` |
| `category_id` | `required`, `integer`, `exists:categories,id` |

`PUT` usa reglas completas porque es reemplazo total (D6).

### 7.2 Filtro (`IndexBookRequest`)

| Campo | Reglas |
|---|---|
| `category` | `nullable`, `integer`, `exists:categories,id` |

### 7.3 Categorías (`StoreCategoryRequest`)

| Campo | Reglas |
|---|---|
| `name` | `required`, `string`, `max:255`, `unique:categories,name` |

### 7.4 Mensajes y 422

- `lang/es/validation.php` traduce las reglas utilizadas y los nombres de atributos.
- `ForceJsonResponse` garantiza JSON aunque el cliente no envíe `Accept`.
- Laravel devuelve `422` con errores por campo.

---

## 8. Lógica de negocio

### 8.1 Eliminación de libros

1. El route model binding resuelve `Book`.
2. Si no existe → `404` antes de entrar al controlador.
3. Si existe → se elimina y se responde `204`.

### 8.2 Eliminación de categorías

1. El route model binding resuelve `Category`; si no existe → `404`.
2. Si `$category->books()->exists()` → `409` con mensaje y sin modificar la categoría.
3. Si no tiene libros → se elimina y se responde `204`.
4. La FK `restrictOnDelete` actúa como segunda barrera.

### 8.3 Filtrado

- Se precarga `category` con `with('category')` para evitar N+1.
- Se ordena por `title`.
- El filtro usa `where('category_id', $id)`.

### 8.4 Integridad

- Crear/actualizar afecta una fila; no requiere transacción explícita.
- La unicidad de categorías está protegida por validación e índice único.
- Los borrados no usan cascadas.

---

## 9. Plan de desarrollo ejecutado

### 9.1 Estado de las fases

| Fase | Estado | Evidencia |
|---|---|---|
| 0. Entorno Docker | Completada | Docker 29.9.3, Compose v5.7.1, app y db healthy. |
| 1. Proyecto y Compose | Completada | Laravel 13.44.1, `Dockerfile`, `docker-compose.yml`, entrypoint. |
| 2. Migraciones y modelos | Completada | Tablas `categories` y `books`, relaciones y factories. |
| 3. Seeders | Completada | 5 categorías y 15 libros idempotentes. |
| 4. Requests y Resources | Completada | 4 Form Requests, 2 Resources, traducciones. |
| 5. Controladores y rutas | Completada | Endpoints sin `/api`, 404/409/422 verificados. |
| 6. Pruebas | Completada | 36 pruebas, 158 aserciones. |
| 7. Documentación y QA | Completada | README, `api.http`, OpenAPI, Pint, plan y changelog. |
| 8. Mejoras opcionales | Parcial | OpenAPI completado; UI, paginación, búsqueda y CI pendientes. |

### Fase 0 — Entorno Docker (completada)

- [x] Verificar Docker Desktop y el daemon.
- [x] Verificar Docker Compose v2+.
- [x] Descargar las imágenes `php:8.5-cli`, `composer:2` y `postgres:17`.
- [x] Confirmar el montaje del workspace.

### Fase 1 — Proyecto Laravel y Compose (completada)

- [x] Crear Laravel 13 con el contenedor `composer:2`.
- [x] Mover el esqueleto a la raíz del proyecto.
- [x] Crear `Dockerfile`, `docker-compose.yml`, `.dockerignore` y entrypoint.
- [x] Instalar dependencias dentro de la imagen durante el build, sin Composer en runtime.
- [x] Configurar `.env` con PostgreSQL.
- [x] Configurar `phpunit.xml` para `biblioteca_test`.
- [x] Registrar `routes/api.php` con `apiPrefix: ''`.
- [x] Verificar `/up` y `php artisan route:list`.

### Fase 2 — Migraciones, modelos y factories (completada)

- [x] Crear `Category` y `Book` con migraciones y factories.
- [x] Definir columnas, unicidad, FK y `restrictOnDelete`.
- [x] Configurar `$fillable`, casts y relaciones.
- [x] Ejecutar `migrate:fresh`.

### Fase 3 — Seeders (completada)

- [x] Crear `CategorySeeder` con 5 categorías.
- [x] Crear `BookSeeder` con 15 libros.
- [x] Registrar ambos en `DatabaseSeeder`.
- [x] Verificar 5 categorías / 15 libros y ejecución idempotente.

### Fase 4 — Form Requests, Resources y traducciones (completada)

- [x] Crear los 4 Form Requests.
- [x] Crear `BookResource` y `CategoryResource`.
- [x] Crear `lang/es/validation.php`.
- [x] Configurar `APP_LOCALE=es`.

### Fase 5 — Controladores, rutas, middleware y errores (completada)

- [x] Crear `ForceJsonResponse` y registrarlo en el grupo `api`.
- [x] Implementar `BookController` (`index`, `store`, `show`, `update`, `destroy`).
- [x] Implementar `CategoryController` (`index`, `store`, `destroy`).
- [x] Definir rutas con `apiResource`.
- [x] Implementar el 409 al borrar una categoría con libros.
- [x] Personalizar el 404 JSON en español.
- [x] Probar los códigos `201`, `200`, `204`, `404`, `409` y `422`.

### Fase 6 — Pruebas (completada)

- [x] Verificar PHPUnit ≥ 12.5.22 (12.5.45).
- [x] Configurar `biblioteca_test` y forzar el entorno de test.
- [x] Implementar `BookApiTest` (25 pruebas).
- [x] Implementar `CategoryApiTest` (7 pruebas).
- [x] Implementar `ApiDocumentationTest` (2 pruebas).
- [x] Ejecutar `php artisan test`.

Resultado: **36 pruebas, 158 aserciones, 0 fallos**.

### Fase 7 — Documentación y QA (completada)

- [x] Escribir `README.md`.
- [x] Crear `docs/api.http`.
- [x] Añadir documentación OpenAPI/Swagger con Scramble.
- [x] Ejecutar Pint y verificar estilo.
- [x] Ejecutar `migrate:fresh --seed` y la suite.
- [x] Actualizar este plan y el changelog.
- [x] Revisar `.gitignore` y documentar el `.env` versionado.
- [ ] Inicializar Git y crear commit/tag (pendiente de confirmación).

### Fase 8 — Mejoras opcionales (parcial)

- [ ] Interfaz Blade mínima.
- [ ] Paginación configurable.
- [ ] Búsqueda por título/autor.
- [x] Documento OpenAPI/Swagger.
- [ ] CI/CD.

---

## 10. Estrategia de pruebas

### 10.1 Configuración

- PHPUnit 12.5.45.
- PostgreSQL `biblioteca_test` creada por el contenedor.
- `RefreshDatabase` en las pruebas feature.
- `phpunit.xml` fuerza `APP_ENV=testing`, `APP_DEBUG=false` y las variables `DB_*` de test.
- Factories para datos aislados.

### 10.2 Cobertura

| Grupo | Casos cubiertos |
|---|---|
| Listado | Lista con datos, lista vacía, estructura JSON. |
| Filtro | Categoría válida, categoría sin libros, inexistente, no numérica. |
| Creación | Válida, descripción nula, título vacío/corto, autor, año ausente/no entero/futuro, categoría ausente/inexistente, cuerpo vacío. |
| Actualización | Válida con cambio de categoría, inexistente, inválida sin modificar, sin `category_id`. |
| Eliminación | Libro existente, libro inexistente, categoría vacía, categoría con libros (409), categoría inexistente. |
| Consulta | Ver libro existente e inexistente. |
| Categorías | Listado, alta, nombre requerido, nombre único, borrado. |
| Mensajes | 404 en español, 409 con mensaje de negocio. |

### 10.3 Comando y resultado

```bash
docker compose exec app php artisan test
```

```text
Tests: 36 passed (158 assertions)
```

### 10.4 Criterio de salida

- `php artisan test` en verde.
- Ninguna prueba usa la base de desarrollo.
- Cada regla del enunciado tiene cobertura.
- `pint --test` limpio.

---

## 11. Criterios de aceptación y trazabilidad

| Requisito / criterio | Implementación | Evidencia |
|---|---|---|
| Listar libros | `BookController@index` + `BookResource` | Pruebas de listado. |
| Crear libro con categoría | `store` + `StoreBookRequest` | Pruebas de creación. |
| Editar libro | `update` + `UpdateBookRequest` | Pruebas de actualización. |
| Eliminar libro | `destroy` + route binding | Pruebas de borrado. |
| Filtrar por categoría | `IndexBookRequest` + `when()` | Pruebas de filtro. |
| `title` requerido, mínimo 3 | Reglas del Form Request | Pruebas 422. |
| `author` requerido | Reglas del Form Request | Pruebas 422. |
| `published_year` válido | `required`, `integer`, `min`, `max` | Pruebas 422. |
| `category_id` existente | `exists:categories,id` | Pruebas 422. |
| Nombre de categoría único | `unique` + índice único | Prueba de duplicado. |
| Libro existente antes de eliminar | Route model binding | Prueba 404. |
| Categoría sin libros antes de eliminar | `books()->exists()` → 409 | Prueba 409. |
| Arquitectura MVC | Controladores, modelos, requests, resources | Estructura del proyecto. |
| Migraciones y seeders | `database/migrations`, `database/seeders` | `migrate:fresh --seed`. |
| Form Requests | 4 clases | Pruebas 422. |
| Eloquent | Relaciones, `with`, route binding | Pruebas y respuestas. |
| Respuestas HTTP | 200/201/204/404/409/422 | Pruebas y verificación manual. |
| Ejecución reproducible | Docker Compose + entrypoint | `docker compose up -d --build`. |
| Documentación OpenAPI | Scramble + UI `/docs/api` | Pruebas de documentación y verificación con curl. |

### Demo de evaluación sugerida

1. `docker compose up -d --build`.
2. `GET /books` → 15 libros con categorías.
3. `GET /books?category=1` → libros de Novela.
4. `POST /books` válido → `201`.
5. `POST /books` con título `AB` → `422` en español.
6. `PUT /books/{id}` → `200`.
7. `DELETE /books/{id}` → `204`; repetir → `404`.
8. `DELETE /categories/1` con libros → `409`.
9. `docker compose exec app php artisan test` → 36 pruebas en verde.

---

## 12. Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Docker Desktop apagado | Media | Alto | Iniciar Docker Desktop antes de `docker compose up`; healthcheck y `depends_on`. |
| Puerto 8000 ocupado | Media | Bajo | Cambiar el mapeo a `8001:8000` en `docker-compose.yml`. |
| Volumen PostgreSQL previo sin `biblioteca_test` | Baja | Medio | `docker compose down -v` para reinicializar el volumen; el script de init crea la base de test. |
| Variables de entorno pisan la configuración de test | Media | Alto | Se eliminaron los `DB_*` del servicio `app`; `phpunit.xml` usa `force="true"`. |
| Versiones de herramientas desactualizadas | Baja | Medio | Composer ≥ 2.11.2 y PHPUnit ≥ 12.5.22 verificados; `composer.lock` fijado. |
| SQLite embebido con CVEs | Nula en runtime | — | No se usa SQLite; PostgreSQL 17.12 en contenedor. |
| Pérdida de datos al borrar el volumen | Baja | Medio | Los seeders permiten reconstruir la demo; el volumen es de desarrollo. |
| Sin autenticación | Alta (por diseño) | Bajo | Fuera de alcance; API local para evaluación. |
| Sin interfaz web | Alta (por diseño) | Bajo | El enunciado define endpoints; `docs/api.http` facilita las pruebas. |
| `.env` versionado con credenciales de desarrollo | Alta (por decisión) | Bajo | Solo valores locales; documentado en README; no usar en producción. |
| Cambios de código no reflejados sin rebuild | Alta | Bajo | La imagen es autocontenida; reconstruir con `docker compose up -d --build`. |
| Primera build requiere internet | Media | Bajo | Composer instala dependencias durante el build; después el arranque es offline. |
| Documentación expuesta | Baja | Medio | Restringida a `local`/`testing` mediante `RestrictedDocsAccess` y el Gate `viewApiDocs`. |
| Repositorio Git sin inicializar | Alta | Bajo | Pendiente de confirmación del usuario para commit/tag. |

---

## 13. Convenciones de trabajo

- **Estilo:** PSR-12, formateado con Laravel Pint.
- **Nombres:** modelos en singular, tablas en plural, rutas REST.
- **Controladores:** delgados; validación en Form Requests y formato en Resources.
- **Respuestas:** envoltura `data`; errores estándar de Laravel.
- **Secretos:** por decisión del proyecto, `.env` se versiona solo con valores de desarrollo (ver README, sección "Entorno versionado"). En producción no debe versionarse; `.env.example` sigue siendo la plantilla.
- **Git:** pendiente de inicialización y primer commit; los commits sugeridos son:
  - `feat: API de libros y categorías con Laravel 13`
  - `test: cobertura de endpoints y validaciones`
  - `docs: README, colección HTTP y plan`
  - `chore: Docker Compose con PHP 8.5 y PostgreSQL 17`
- **Definition of Done:** `docker compose up -d --build` funciona, `/up` responde, `migrate:fresh --seed` deja 5/15, `php artisan test` en verde y Pint limpio.

---

## 14. Entregables

1. Aplicación Laravel 13 ejecutable con Docker Compose (imagen autocontenida con código y dependencias).
2. `Dockerfile`, `docker-compose.yml`, `.dockerignore` y entrypoint.
3. PostgreSQL 17 en contenedor con bases `biblioteca` y `biblioteca_test`.
4. Migraciones, factories y seeders de categorías y libros.
5. Form Requests, API Resources, controladores y rutas exactas.
6. Suite de 36 pruebas feature contra PostgreSQL.
7. `README.md` con inicio rápido, endpoints, ejemplos y documentación OpenAPI.
8. `docs/api.http` para VS Code REST Client.
9. Documentación OpenAPI interactiva en `/docs/api` y especificación en `/docs/api.json` (Scramble).
10. `PLAN_DESARROLLO.md` actualizado con plan, decisiones, pruebas y changelog.
11. Repositorio Git y etiqueta `v1.3.0` (pendiente de confirmación).

---

## 15. Comandos de referencia

```bash
# Levantar / reconstruir
docker compose up -d --build

# Estado y logs
docker compose ps
docker compose logs -f app

# Laravel
docker compose exec app php artisan --version
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan tinker
docker compose exec app php artisan test

# Dependencias y estilo
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/pint --test

# Base de datos
docker compose exec db psql -U biblioteca -d biblioteca
docker compose exec db psql -U biblioteca -d biblioteca_test

# Detener / borrar volumen
docker compose down
docker compose down -v
```

---

## 16. Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y [SemVer](https://semver.org/lang/es/).

### [1.3.0] — 2026-11-24

#### Added

- Revisión de `.gitignore` y `.dockerignore` y limpieza de artefactos generados.
- Documentación de la excepción consciente: `.env` se versiona con valores de desarrollo.
- Guía en el README con los archivos que no se versionan.

#### Changed

- `.env` deja de estar ignorado por decisión del desarrollador; `.env.backup`, `.env.production` y `.env.local` siguen excluidos.

### [1.2.0] — 2026-11-24

#### Added

- Imagen Docker autocontenida: instala Composer, dependencias, código y `vendor` durante el build.
- Healthcheck del servicio `app` y arranque sin Composer en runtime.

#### Changed

- `docker-compose.yml` ya no monta el código del host; `docker compose up -d --build` es el flujo principal.
- El entrypoint solo crea `.env`, genera la clave, descubre paquetes, migra, seedea y sirve.

### [1.1.0] — 2026-11-24

#### Added

- Documentación OpenAPI 3.1 generada con Scramble en `/docs/api` y `/docs/api.json`.
- Gate `viewApiDocs` y restricción de acceso a la documentación en `local`/`testing`.
- `ApiDocumentationTest` con 2 pruebas y actualización del README.

#### Changed

- La suite pasa a 36 pruebas y 158 aserciones.

### [1.0.0] — 2026-11-24

#### Added

- API REST completa de libros: listar, crear, ver, actualizar y eliminar.
- Filtro de libros por categoría.
- Endpoints de soporte de categorías con nombre único y bloqueo de borrado con libros.
- Migraciones, modelos Eloquent, factories y seeders (5 categorías, 15 libros).
- Form Requests, API Resources, `ForceJsonResponse` y mensajes de validación en español.
- Dockerfile, `docker-compose.yml`, entrypoint idempotente y PostgreSQL 17 en contenedor.
- Base de test `biblioteca_test` y 34 pruebas feature (146 aserciones).
- README, colección `docs/api.http` y plan actualizado.

#### Changed

- El entorno se migró de la propuesta inicial con SQLite a **Docker Compose + PostgreSQL 17**.
- Los endpoints se exponen sin prefijo `/api` para respetar el enunciado.
- `APP_DEBUG=false` por defecto para respuestas de error limpias.
- El handler 404 se personalizó para `NotFoundHttpException`, devolviendo JSON en español.

### [0.1.2] — 2026-11-24

#### Added

- Sección de versiones mínimas por seguridad: Composer ≥ 2.11.2 y PHPUnit ≥ 12.5.22.
- Verificación de SQLite y nota de riesgo.

### [0.1.1] — 2026-11-24

#### Added

- Verificación de disponibilidad de Laravel 13 y fallback de PHP.
- Decisiones D15–D18.
- Caso de prueba T19b para `PUT` sin `category_id`.
- Sección para cambiar de motor de base de datos.

### [0.1.0] — 2026-11-24

#### Added

- Documento inicial de planificación con enunciado, arquitectura, contrato de API, fases, matriz de pruebas y riesgos.

---

## 17. Próximos pasos

1. Confirmar si se desea inicializar Git, crear commits por fase y etiquetar `v1.3.0`.
2. (Opcional) Añadir interfaz Blade, paginación o búsqueda.
3. (Opcional) Publicar la imagen en un registro o añadir CI.
4. Entregar el proyecto con `docker compose up -d --build` como comando principal.

---

## Referencias

- Laravel 13 — Documentación: <https://laravel.com/docs/13.x>
- Laravel 13 — Routing y `apiPrefix`: <https://laravel.com/docs/13.x/routing>
- Laravel 13 — Validación: <https://laravel.com/docs/13.x/validation>
- Laravel 13 — Eloquent: <https://laravel.com/docs/13.x/eloquent>
- Laravel 13 — Testing: <https://laravel.com/docs/13.x/testing>
- Docker Compose: <https://docs.docker.com/compose/>
- PostgreSQL 17: <https://www.postgresql.org/docs/17/>
- Composer 2.11.2 — changelog: <https://getcomposer.org/changelog/2.11.2>
- GHSA-6prc-h956-hxjq: <https://symfony.com/blog/ghsa-6prc-h956-hxjq-filesystem-path-treats-any-prefix-before-as-a-root-letting-paths-escape-their-base-directory>
- CVE-2026-41570: <https://nvd.nist.gov/vuln/detail/CVE-2026-41570>
- CVE-2026-119371: <https://nvd.nist.gov/vuln/detail/CVE-2026-119371>
- Keep a Changelog: <https://keepachangelog.com/es-ES/1.1.0/>
- Semantic Versioning: <https://semver.org/lang/es/>
