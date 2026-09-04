# linkedcode/notenv

Configuración estructurada basada en arrays asociativos tipados para PHP, pensada para reemplazar variables de entorno planas (`.env`).

> **Breaking change:** `config/config.php` ya no se lee. Los overrides van en `config/config.<env>.php`, con `<env>` en `dev|test|prod`. Para migrar un proyecto existente alcanza con renombrar `config/config.php` a `config/config.dev.php` (o `config.prod.php` en un servidor) y actualizar el `.gitignore`. Un `APP_ENV` que no sea uno de esos tres ahora lanza excepción.

## Instalación

```bash
composer require linkedcode/notenv
```

## Uso rápido

El cargador busca los archivos de configuración en el directorio `config/` del path provisto.

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Linkedcode\NotEnv\Loader;

// Busca config/common.php y config/config.<APP_ENV>.php
$config = Loader::load(__DIR__);

echo $config->get('app.name');           // Resuelve de common.php
echo $config->get('db.host');            // Resuelve de config.<env>.php
```

## Estructura de archivos de configuración

Crea un directorio `config/` en la raíz de tu proyecto. Se cargan dos archivos:

1. **`config/common.php`**: la configuración de la aplicación. Versionado, **obligatorio**. Es el archivo principal: acá viven todas las claves, estructuradas. Sin credenciales ni secretos.
2. **`config/config.<env>.php`**: lo que cambia en el entorno activo. **Opcional**, y se mergea recursivamente encima de `common.php`.

Los entornos válidos son **`dev`**, **`test`** y **`prod`**, y salen de `APP_ENV`. Sin `APP_ENV` definido, el entorno es `dev`.

**Se carga un solo `config.<env>.php`: el del entorno activo.** Los demás no se leen, así que no pueden pisarse entre sí — `config.dev.php` y `config.test.php` conviven en la misma máquina sin que uno se lleve puesta la base del otro.

Se aceptan las formas largas y se normalizan a la corta (`production` → `prod`, `development` → `dev`, `testing` → `test`), sin distinguir mayúsculas. Un valor fuera de esa lista es un error, no un default silencioso: caer a `dev` en un servidor dejaría `debug` activo y las cookies inseguras.

`APP_ENV` se lee de `$_ENV` y, si ahí no está, de `getenv()`: son dos almacenes separados y el valor puede llegar por cualquiera de los dos (`$_ENV` sólo se puebla si `variables_order` incluye `"E"`). También se puede pasar explícito:

```php
$config = Loader::load(__DIR__, 'test');
```

Si el archivo del entorno no existe, no es un error: queda `common.php` tal cual.

Ejemplo de `config/common.php`:
```php
<?php
return [
    'app' => [
        'name' => 'Mi Aplicación',
        'env'  => 'production',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
    ],
];
```

Ejemplo de `config/config.test.php` (entorno, con `APP_ENV=test`):
```php
<?php
return [
    'db' => [
        'port'   => 3307,          // el MySQL efímero de la suite
        'dbname' => 'app_test',
    ],
];
```

Ejemplo de `config/config.dev.php` (la máquina de desarrollo, en `.gitignore`):
```php
<?php
return [
    'app' => [
        'debug' => true,          // sobreescribe
    ],
    'db' => [
        'host' => 'db.local',     // sobreescribe
        'user' => 'root',         // agrega
    ],
];
```

---

## Reglas y Convenciones (desde la Arquitectura Global)

### ✅ Hacer (Dos)
* Diseñar tu configuración pensando en arrays anidados asociativos mergeables.
* Separar estrictamente los valores genéricos (en `common.php`) de las credenciales y variables específicas del entorno (en `config.<env>.php`).
* Versionar `config.test.php` si no tiene secretos: la suite arranca sin configuración manual. `config.dev.php` y `config.prod.php` normalmente van al `.gitignore`.

### ❌ No Hacer (Donts)
* **Evitar dotenv:** No necesitas `.env` ni un parser de dotenv. Los archivos de configuración son PHP, así que leer del entorno es un override más.
* **No leer el entorno desde el código de aplicación:** nunca llames a `getenv()` o `$_ENV` fuera de `config/`. Toda la configuración debe fluir a través del objeto `Config` provisto por este cargador.

## Despliegue (Docker)

Lo que se despliega es el `config.<env>.php` que corresponda, montado o copiado en la imagen — con `APP_ENV=prod`, `config.prod.php`.

Si la plataforma inyecta la configuración como variables de entorno y no hay dónde poner un archivo, ese es el único lugar donde se las lee:

```php
<?php
// config/config.prod.php -- no versionado si trae secretos
return [
    'db' => [
        'host' => $_ENV['DB_HOST'],
        'port' => (int) $_ENV['DB_PORT'],
        'pass' => $_ENV['DB_PASS'],
    ],
];
```

Sin `??`: si falta una de esas variables conviene que reviente al arrancar y no que caiga a un default y conecte a otro lado. Los valores de desarrollo van en `config.dev.php`, no en `common.php`.

La configuración se lee en cada carga, sin caché en disco: un redeploy con variables distintas toma efecto inmediatamente. El coste es despreciable — son dos `require` de arrays que OPcache mantiene compilados en memoria.
