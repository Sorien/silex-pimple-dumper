## Install

Composer

```json
    "require": {
        "sorien/silex-pimple-dumper": "~1.0"
    }
```

Register

```php
	$app->register(new Sorien\Provider\PimpleDumpProvider());
```

plugin will output container dump (`pimple.json`) to parent directory of vendor's dir, if you need to use different path set parameter `['dump.path']`

if you are in dev enviroment `['debug'] = true`, program will generate dump **automatically on finish event** or just visit `http://your_project/_dump` 
