<?php

namespace Sorien\Provider;

use Exception;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PimpleDumpProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    private $outOfRequestScopeTypes = array();
    private $processed = false;

    private function dump($app)
    {
        /** @var \Silex\Application $app */
        $map = array();
        $fileName = $app['dump.path'].'/pimple.json';

        foreach ($app->keys() as $name) {
            if ($name === 'dump.path') {
                continue;
            }

            try {
                $element = $app[$name];

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

                $map[] = array('name' => $name, 'type' => $type, 'value' => $value);
            } catch (Exception $e) {
                if (isset($this->outOfRequestScopeTypes[$name])) {
                    $map[] = array('name' => $name, 'type' => 'class', 'value' => $this->outOfRequestScopeTypes[$name]);
                }
            }
        }

        $content = json_encode($map, JSON_PRETTY_PRINT);

        if (!file_exists($fileName)) {
            file_put_contents($fileName, json_encode($map, JSON_PRETTY_PRINT));
        } else {
            $oldContent = file_get_contents($fileName);
            // prevent file lastModified time change
            if ($content != $oldContent) {
                file_put_contents($fileName, json_encode($map, JSON_PRETTY_PRINT));
            }
        }

        $processed = true;
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->get('/_dump', function() use ($app) {

            $this->dump($app);

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

            $app->after(function (Request $request, Response $response) use ($app) {
                $this->outOfRequestScopeTypes['request'] = get_class($app['request']);
            });

            $obj = $this;

            $app->finish(function (Request $request, Response $response) use ($app, $obj) {
                if (!$obj->processed) {
                    $this->dump($app);
                }
            }, -1);
        }
    }
}
