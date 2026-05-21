# Flujo de Incoterms y por qué se usa `apiResource` en `api.php`

Este documento explica:
- Dónde está definido el flujo CRUD de incoterms.
- Qué endpoints reales se exponen.
- Por qué en `routes/api.php` se usa `Route::apiResource(...)`.

## 1) Dónde está el flujo

El flujo principal está en:
- `routes/api.php` (registro de rutas)
- `app/Http/Controllers/Api/Admin/IncotermController.php` (lógica CRUD)
- `app/Http/Controllers/Concerns/AuthorizesApiRequests.php` (autorización por rol)
- `app/Models/TipusIncoterm.php` (modelo principal)
- `database/migrations/2026_04_08_160000_create_nerevian_domain_tables.php` (estructura de tablas y cascadas)

## 2) Definición de rutas en `api.php`

En el bloque admin se registra:

```php
Route::apiResource('incoterms', AdminIncotermController::class)
    ->parameters(['incoterms' => 'incoterm']);
```

Eso está bajo:
- middleware `auth:sanctum`
- grupo prefijo `/api/admin`

Por tanto, las rutas finales quedan:

1. `GET /api/admin/incoterms` -> `index`
2. `POST /api/admin/incoterms` -> `store`
3. `GET /api/admin/incoterms/{incoterm}` -> `show`
4. `PUT/PATCH /api/admin/incoterms/{incoterm}` -> `update`
5. `DELETE /api/admin/incoterms/{incoterm}` -> `destroy`

## 3) Por qué usar `apiResource`

Se usa `apiResource` porque este proyecto expone una API (no vistas Blade para formularios). Ventajas:

- Estandariza el CRUD REST automáticamente.
- Evita duplicar 5 declaraciones de rutas manuales.
- Mantiene convención Laravel (más legible para cualquier dev Laravel).
- Reduce errores de mantenimiento al crecer el módulo.
- Incluye solo rutas de API (sin `create` y `edit` HTML).

Diferencia importante:
- `Route::resource(...)` incluye `create` y `edit` (pensado para vistas web).
- `Route::apiResource(...)` NO incluye `create` ni `edit` (pensado para APIs JSON).

## 4) Qué hace cada operación en el controlador

### `store` (crear)
- Verifica rol `admin`.
- Valida `codi`, `nom`, `tracking_step_ids`.
- Crea registro en `tipus_incoterms`.
- Sincroniza pasos en tabla pivote `incoterms`.
- Devuelve `201` con el incoterm formateado.

### `update` (editar)
- Verifica rol `admin`.
- Route model binding resuelve `{incoterm}` a `TipusIncoterm`.
- Valida (incluyendo unique de `codi` ignorando el propio ID).
- Actualiza datos y resync de `tracking_step_ids`.
- Devuelve `200`.

### `destroy` (eliminar)
- Verifica rol `admin`.
- Elimina `TipusIncoterm`.
- La base de datos aplica cascada en la pivote `incoterms`.
- Devuelve `200`.

## 5) Nota sobre `->parameters(['incoterms' => 'incoterm'])`

Esto renombra el parámetro de ruta para que Laravel inyecte correctamente el modelo en el método:

```php
public function update(Request $request, TipusIncoterm $incoterm)
```

Sin ese ajuste, el nombre del parámetro puede no coincidir con la firma esperada y complica el binding/legibilidad.

## 6) Resumen corto

`apiResource` está en `api.php` porque este módulo es un CRUD REST puro para frontend/API, y Laravel ya provee exactamente las rutas necesarias de forma consistente, mantenible y alineada con el controlador `IncotermController`.
