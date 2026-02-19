Usage:

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Linkedcode\NotEnv\Loader;

$config = Loader::load(__DIR__); // busca config/ y var/cache/

echo $config->get('app.name');           // de common.php o config.php
echo $config->get('database.host');      // de config.php
```
