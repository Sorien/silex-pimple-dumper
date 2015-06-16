<?php

namespace Sorien\Provider;

use Exception;
use Pimple as Container;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PimpleDumpProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    private $outOfRequestScopeTypes = array();
    private $processed = false;

    public function dump(Container $container)
    {
        $map = $this->parseContainer($container);

        $fileName = $container['dump.path'].'/pimple.json';
        $this->write($map, $fileName);

        $this->processed = true;
    }

    /**
     * Generate a mapping of the container's values
     *
     * @param Container $container
     *
     * @return array
     */
    protected function parseContainer(Container $container)
    {
        $map = array();

        foreach ($container->keys() as $name) {
            if ($name === 'dump.path') {
                continue;
            }

            if ($item = $this->parseItem($container, $name)) {
                $map[] = $item;
            }
        }

        return $map;
    }

    /**
     * Parse the item's type and value
     *
     * @param Container $container
     * @param string    $name
     *
     * @return array|null
     */
    protected function parseItem(Container $container, $name)
    {
        try {
            $element = $container[$name];
        } catch (Exception $e) {
            if (isset($this->outOfRequestScopeTypes[$name])) {
                return array('name' => $name, 'type' => 'class', 'value' => $this->outOfRequestScopeTypes[$name]);
            }
            return null;
        }

        if (is_object($element)) {
            if ($element instanceof \Closure) {
                $type = 'closure';
                $value = '';
            } else {
                $type = 'class';
                $value = get_class($element);
            }
        } else if (is_array($element)) {
            $type = 'array';
            $value = '';
        } else if (is_string($element)) {
            $type = 'string';
            $value = $element;
        } else if (is_integer($element)) {
            $type = 'int';
            $value = $element;
        } else if (is_float($element)) {
            $type = 'float';
            $value = $element;
        } else if (is_bool($element)) {
            $type = 'bool';
            $value = $element;
        } else if (is_null($element)) {
            $type = 'null';
            $value = '';
        } else {
            $type = 'unknown';
            $value = gettype($element);
        }

        return array('name' => $name, 'type' => $type, 'value' => $value);
    }

    /**
     * Dump mapping to file
     *
     * @param array  $map
     * @param string $fileName
     */
    protected function write($map, $fileName)
    {
        $content = json_encode($map, JSON_PRETTY_PRINT);

        if (!file_exists($fileName)) {
            file_put_contents($fileName, $content);
            return;
        }

        $oldContent = file_get_contents($fileName);
        // prevent file lastModified time change
        if ($content !== $oldContent) {
            file_put_contents($fileName, $content);
        }
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;
        $controllers->get('/_dump', function() use ($app, $self) {

            $self->dump($app);

            return 'Pimple Container dumped.';
        });

        return $controllers;
    }

    public function register(Application $app)
    {
        $app->mount('/', $this);

        if (!isset($app['dump.path'])) {
            // parent of vendor directory
            $baseDir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
            $app['dump.path'] = $baseDir;
        }
    }

    public function boot(Application $app)
    {
        if ($app['debug']) {

            $self = $this;

            $app->after(function (Request $request, Response $response) use ($app, $self) {
                $self->outOfRequestScopeTypes['request'] = get_class($app['request']);
            });

            $app->finish(function (Request $request, Response $response) use ($app, $self) {
                if (!$self->processed) {
                    $self->dump($app);
                }
            }, -1);
        }
    }
}
