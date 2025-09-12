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

#### Personalizar el nombre del causante del evento

Por defecto, el campo `causer_name` se llena con el atributo `name` del usuario autenticado. Si deseas usar un atributo diferente o una combinación de atributos (por ejemplo, nombre y apellido), puedes añadir un accesor `getResolvedDescriptionAttribute` a tu modelo `User`.

```php
// En tu modelo App\Models\User

class User extends Authenticatable
{
    // ...

    /**
     * Devuelve el nombre completo del usuario.
     */
    public function getResolvedDescriptionAttribute()
    {
        return $this->fullname; // o $this->first_name . ' ' . $this->last_name;
    }
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

### 3. Registrar cambios en relaciones (attach/detach/sync) de forma estándar

Cuando sincronizas relaciones muchos-a-muchos (belongsToMany), suele ser útil registrar qué IDs se agregaron o quitaron, junto con etiquetas legibles. Para esto, el paquete expone un método genérico en el logger:

Contratos rápidos:
- Input
    - relationName: nombre lógico de la relación (string)
    - originalIds: array de IDs antes de sync
    - newIds: array de IDs después de sync
    - labelsById: map opcional [id => label]
    - meta: array opcional para meta-datos (ej: ['label' => 'Grupos'])
- Output
    - Se agrega un ítem a properties.changes[] con old/new/added/removed y sus labels

Ejemplo práctico con Users y Groups (belongsToMany):

```php
use App\Models\User;
use AporteWeb\Dashboard\Models\Group;
use Illuminate\Support\Facades\Auth;

// En tu controlador o servicio, antes y después de sync
$user = $id ? User::where('uuid', $id)->first() : new User;

// Capturar IDs originales (si existe)
$originalGroupIds = $user->exists ? $user->groups()->pluck('id')->all() : [];

// Guardar datos del usuario como necesites
// ... $user->fill(...); $user->save();

// Convertir UUIDs recibidos a IDs y sincronizar
$groupUuids = is_array($request->groups) ? $request->groups : explode(',', (string) $request->groups);
$groupUuids = array_filter($groupUuids);
$newGroupIds = $groupUuids ? Group::whereIn('uuid', $groupUuids)->pluck('id')->all() : [];
$user->groups()->sync($newGroupIds);

// Preparar labels opcionales para mejores mensajes
$allIds = array_values(array_unique(array_merge($originalGroupIds, $newGroupIds)));
$labelsById = $allIds ? Group::whereIn('id', $allIds)->pluck('name', 'id')->toArray() : [];

// Descripción amigable
$actorName = Auth::user() ? Auth::user()->name : 'system';
$description = $user->wasRecentlyCreated
        ? "El usuario {$actorName} asignó grupos al usuario {$user->id}"
        : "El usuario {$actorName} actualizó los grupos del usuario {$user->id}";

// Log estandarizado del diff de relación
activity()
        ->onModel($user)                 // setea subject, log_name, ref_name y ref
        ->forEvent($user->wasRecentlyCreated ? 'create' : 'update')
        ->describe($description)
        ->logRelationSync('groups', $originalGroupIds, $newGroupIds, $labelsById, ['label' => 'Grupos']);
```

El registro generado incluirá en `properties.changes[0]`:

```json
{
    "field": "groups",
    "label": "Grupos",
    "old_value": [1, 2],
    "new_value": [2, 3],
    "old_labels": ["Admin", "Ventas"],
    "new_labels": ["Ventas", "Compras"],
    "added": {"ids": [3], "labels": ["Compras"]},
    "removed": {"ids": [1], "labels": ["Admin"]}
}
```

#### Otros métodos útiles del logger

Puedes encadenar métodos para personalizar los campos de Activity de forma declarativa:

```php
activity()
    ->onModel($subject)               // subject + log_name/ref_name/ref
    ->onLogName('custom_log')         // sobreescribe log_name
    ->forEvent('update')              // event libre ('create', 'update', 'delete', etc.)
    ->withRef('uuid', $subject->uuid) // setea ref_name/ref a mano si quieres
    ->withProperties(['ctx' => 'val'])
    ->withProperty('trace_id', $trace)
    ->describe('Texto descriptivo')
    ->log('Descripción final opcional');
```

Notas:
- `activity()` helper se auto-carga desde el ServiceProvider del paquete.
- `logRelationSync` no guarda si no hay cambios (no added/removed).
- `labelsById` es opcional, pero recomendado para UI.
- Performance: si manejas relaciones muy grandes, considera limitar IDs o paginar para construir `labelsById`.

## Referencias
Esta libreria esta inspirada en el paquete [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)

## Licencia

Este paquete se distribuye bajo la licencia MIT. Consulta el archivo `LICENSE` para más detalles.
