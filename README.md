# linkedcode/notenv

Configuración estructurada basada en arrays asociativos tipados para PHP, pensada para reemplazar variables de entorno planas (`.env`).

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

$config = Loader::load(__DIR__); // busca config/common.php y config/config.php

echo $config->get('app.name');           // Resuelve de common.php o de config.php
echo $config->get('database.host');      // Resuelve de config.php
```

## Estructura de archivos de configuración

Crea un directorio `config/` en la raíz de tu proyecto:
1. **`config/common.php`**: Contiene la configuración base por defecto de tu aplicación (valores seguros para desarrollo o producción).
2. **`config/config.php`**: Archivo local de configuración específica del entorno (base de datos local, llaves de API de desarrollo). **Este archivo debe estar excluido en tu `.gitignore`**. Es **opcional**: si no existe, se usa únicamente `common.php`.

Ejemplo de `config/common.php`:
```php
<?php
return [
    'app' => [
        'name' => 'Mi Aplicación',
        'env'  => 'production',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
    ],
];
```

Ejemplo de `config/config.php` (local):
```php
<?php
return [
    'app' => [
        'env'  => 'development', // sobreescribe
    ],
    'database' => [
        'host' => 'db.local',     // sobreescribe
        'user' => 'root',         // agrega
    ],
];
```

---

## Reglas y Convenciones (desde la Arquitectura Global)

### ✅ Hacer (Dos)
* Diseñar tu configuración pensando en arrays anidados asociativos mergeables.
* Separar estrictamente los valores genéricos (en `common.php`) de las credenciales y variables específicas del servidor o local (en `config.php`).

### ❌ No Hacer (Donts)
* **Evitar dotenv:** No necesitas `.env` ni un parser de dotenv. Los archivos de configuración son PHP, así que leer del entorno es un override más.
* **No leer el entorno desde el código de aplicación:** nunca llames a `getenv()` o `$_ENV` fuera de `config/`. Toda la configuración debe fluir a través del objeto `Config` provisto por este cargador.

## Despliegue con variables de entorno (Docker)

En un contenedor la configuración suele venir inyectada como variables de entorno y `config.php` no existe (está gitignoreado). Como los archivos de configuración son PHP, se leen directamente desde `common.php`:

```php
<?php
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'pass' => $_ENV['DB_PASS'] ?? null,
    ],
];
```

En desarrollo, sin esas variables definidas, quedan los valores por defecto y `config.php` los sobreescribe como siempre.

La configuración se lee en cada carga, sin caché en disco: un redeploy con variables distintas toma efecto inmediatamente. El coste es despreciable — son dos `require` de arrays que OPcache mantiene compilados en memoria.
