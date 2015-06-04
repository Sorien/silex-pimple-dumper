<?php

namespace Sorien\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class PimpleDumpProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->get('/_dump', function() use ($app) {

            $map = array();

            foreach ($app->keys() as $name)
            {
                $element = $app[$name];

                if (is_object($element))
                {
                    if ($element instanceof \Closure)
                    {
                        $type = 'closure';
                        $value = '';
                    }
                    else
                    {
                        $type = 'class';
                        $value = get_class($element);
                    }
                }

                else if (is_array($element))
                {
                    $type = '\array';
                    $value = '';
                }

                else if (is_string($element))
                {
                    $type = 'string';
                    $value = $element;
                }

                else if (is_integer($element))
                {
                    $type = 'int';
                    $value = $element;
                }

                else if (is_float($element))
                {
                    $type = 'float';
                    $value = $element;
                }

                else if (is_bool($element))
                {
                    $type = 'bool';
                    $value = $element;
                }

                else if (is_null($element))
                {
                    $type = 'null';
                    $value = '';
                }
                else
                {
                    $type = 'unknown';
                    $value = gettype($element);
                }

                $map[] = array('name' => $name, 'type' => $type, 'value' => $value);
            }

            file_put_contents($app['dump.path'].'/pimple.json', json_encode($map, JSON_PRETTY_PRINT));

            return 'Pimple Container dumped.';
        });

        return $controllers;
    }

    public function register(Application $app)
    {
        $app->mount('/', $this);
    }

    public function boot(Application $app)
    {
    }
}