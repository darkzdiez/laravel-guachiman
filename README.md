# Laravel Guachiman

Un registrador de actividad simple y flexible para tus aplicaciones de Laravel. "Guachimán" es un término coloquial en algunos países de habla hispana para un vigilante o guardián, que es exactamente lo que hace este paquete: vigila y registra las actividades en tu aplicación.

## Instalación

Puedes instalar el paquete a través de composer:

```bash
composer require aporteweb/laravel-guachiman
```

> **Nota:** Si estás trabajando en un entorno de desarrollo y necesitas la última versión, puedes usar `dev-main`:
>
> ```bash
> composer require aporteweb/laravel-guachiman:dev-main
> ```

El proveedor de servicios se registrará automáticamente gracias al auto-descubrimiento de paquetes de Laravel.

### Publicar archivos

Debes publicar la migración para crear la tabla de registro de actividad:

```bash
php artisan vendor:publish --provider="AporteWeb\Guachiman\Providers\GuachimanServiceProvider" --tag="migrations"
```

Después, ejecuta las migraciones:

```bash
php artisan migrate
```

Si deseas personalizar la configuración, puedes publicar el archivo de configuración:

```bash
php artisan vendor:publish --provider="AporteWeb\Guachiman\Providers\GuachimanServiceProvider" --tag="config"
```

Esto creará un archivo `config/guachiman.php` en tu aplicación donde puedes cambiar el nombre de la tabla y la conexión de la base de datos.

## Uso

### 1. Registro automático de eventos de modelo

Para registrar automáticamente los eventos `created`, `updated` y `deleted` de tus modelos de Eloquent, solo necesitas usar el trait `AporteWeb\Guachiman\Traits\LogsChanges` en tu modelo.

```php
use Illuminate\Database\Eloquent\Model;
use AporteWeb\Guachiman\Traits\LogsChanges;

class Product extends Model
{
    use LogsChanges;

    // ...
}
```

#### Especificar qué atributos registrar

Por defecto, todos los atributos que cambien se registrarán en el evento `updated`. Puedes especificar qué atributos quieres registrar añadiendo una propiedad `$loggable` a tu modelo.

```php
class Product extends Model
{
    use LogsChanges;

    protected $loggable = ['name', 'price'];

    // ...
}
```

#### Añadir etiquetas personalizadas a los atributos

Puedes definir etiquetas personalizadas para los atributos registrados. Estas etiquetas se guardarán en el JSON de propiedades, lo que puede ser útil para mostrar los cambios de una manera más amigable en la interfaz de usuario.

Para ello, añade una propiedad pública `$loggableLabels` a tu modelo:

```php
class Product extends Model
{
    use LogsChanges;

    protected $loggable = ['name', 'price', 'stock_quantity'];

    public $loggableLabels = [
        'name' => 'Nombre del Producto',
        'price' => 'Precio',
        'stock_quantity' => 'Cantidad en Stock',
    ];

    // ...
}
```

El registro de actividad para una actualización de `stock_quantity` se vería así:

```json
{
    "changes": [
        {
            "field": "stock_quantity",
            "label": "Cantidad en Stock",
            "new_value": "10",
            "old_value": "15"
        }
    ]
}
```

### 2. Registro manual de actividad

También puedes registrar actividades manualmente usando la función de ayuda `activity()`. Esto es útil para registrar acciones que no están directamente relacionadas con un evento de modelo.

#### Uso básico

```php
activity()->log('El usuario ha exportado un informe de ventas.');
```

#### Métodos encadenados

El helper proporciona varios métodos para añadir más contexto a tu registro de actividad.

```php
activity()
   ->causedBy($user) // El usuario que causó el evento (opcional, se infiere el usuario autenticado)
   ->performedOn($someModel) // El modelo sobre el que trata el evento
   ->withProperties(['key' => 'value']) // Añade propiedades personalizadas en formato JSON
   ->log('Ha ocurrido algo'); // La descripción del evento
```

**Ejemplo práctico:**

```php
use App\Models\Product;
use App\Models\User;

$user = User::find(1);
$product = Product::find(1);

activity()
    ->causedBy($user)
    ->performedOn($product)
    ->withProperties(['action' => 'export', 'format' => 'pdf'])
    ->log("El producto {$product->name} fue exportado");

/*
Esto creará un registro de actividad con:
- causer: el usuario con id 1
- subject: el producto con id 1
- properties: {"action": "export", "format": "pdf"}
- description: "El producto NombreDelProducto fue exportado"
*/
```
