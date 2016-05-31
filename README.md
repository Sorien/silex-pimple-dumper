Installation
------------

Install  using [composer](http://getcomposer.org/).

**Silex 2.x**

```bash
composer require sorien/silex-pimple-dumper "~2.0"
```

**Silex 1.x**

```bash
composer require sorien/silex-pimple-dumper "~1.0"
```

Registering
-----------
```php
$app->register(new Sorien\Provider\PimpleDumpProvider());
```

The service will write the container dump file to Composer's parent directory (`vendor/../pimple.json`) by default. Set the ~~`dump.path`~~ `pimpledump.output_dir` parameter if you need to specify the output directory path.
- Example: `$app['pimpledump.output_dir'] = '/tmp'`

A container dump can be manually invoked by making a `GET` request to `http://your_project/_dump` or, if provided, the route path pattern specified by the `pimpledump.trigger_route_pattern` parameter.
- Example: `$app['pimpledump.trigger_route_pattern'] = '/_dump_pimple'`

If you are in a dev enviroment (`$app['debug'] = true`) the service will *__automatically dump the container during shutdown__ if it wasn't done earlier within the lifecycle*.
