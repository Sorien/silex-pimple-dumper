## Install

Composer

```json
    "require": {
        "sorien/silex-pimple-dumper": "~1.0"
    }
```

Register

```php
	$app->register(new Sorien\Provider\PimpleDumpProvider(), array(
	    'dump.path' => __DIR__.'/..',
	));
```

`dump.path` is path to project root directory

Visit http://your_project/_dump to dump Pimple container to file "pimple.json"