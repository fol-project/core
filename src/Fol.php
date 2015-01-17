<?php
/**
 * Fol\Fol
 *
 * Manages global data
 */

namespace Fol;

use Composer\Script\Event;

class Fol
{
    private static $variables = [];
    private static $registeredServices = [];
    private static $services = [];
    private static $basePath;

    /**
     * Init the fol basic operations
     *
     * @param string $basePath The base path of the fol installation
     * @param string $env_file The file with the environment variables
     */
    public static function init($basePath, $env_file = 'env.local.php')
    {
        self::$basePath = self::fixPath(str_replace('\\', '/', $basePath));

        self::$services['composer'] = require self::getPath('vendor/autoload.php');

        //Environment variables
        if ($env_file) {
            $variables = require self::getPath('env.local.php');
        } else {
            $variables = [];
        }

        if (empty($variables['BASE_URL']) || php_sapi_name() === 'cli-server') {
            $variables['BASE_URL'] = ($_SERVER['HTTPS'] === 'on' ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].':';

            if (!empty($_SERVER['X_FORWARDED_PORT'])) {
                $variables['BASE_URL'] .= $_SERVER['X_FORWARDED_PORT'];
            } elseif (!empty($_SERVER['SERVER_PORT'])) {
                $variables['BASE_URL'] .= $_SERVER['SERVER_PORT'];
            } else {
                $variables['BASE_URL'] .= 80;
            }
        }

        self::$variables = $variables;
    }

    /**
     * Register a new service to get in lazy mode
     *
     * @param string   $name     The service name
     * @param \Closure $resolver A function that returns a service instance
     */
    public static function register($name, \Closure $resolver = null)
    {
        self::$registeredServices[$name] = $resolver;
    }

    /**
     * Save a service
     *
     * @param string $name    The name of the service
     * @param mixed  $service The name of the service
     */
    public static function set($name, $service)
    {
        return self::$services[$name] = $service;
    }

    /**
     * Get a service
     *
     * @param string $name The name of the service
     */
    public static function get($name)
    {
        if (isset(self::$services[$name])) {
            return self::$services[$name];
        }

        if (isset(self::$registeredServices[$name])) {
            return self::$services[$name] = call_user_func(self::$registeredServices[$name]);
        }
    }

    /**
     * Deletes a service
     *
     * @param string $name The name of the service
     */
    public static function delete($name)
    {
        unset(self::$services[$name]);
    }

    /**
     * Set an environmet variable
     *
     * @param string $name
     * @param mixed  $value
     */
    public static function setEnv($name, $value)
    {
        self::$variables[$name] = $value;
    }

    /**
     * Get an environmet variable
     *
     * @param string $name The name of the variable
     */
    public static function getEnv($name)
    {
        return isset(self::$variables[$name]) ? self::$variables[$name] : null;
    }

    /**
     * Set the base path of the fol installation
     *
     * @return string
     */
    public static function getPath()
    {
        return self::fixPath(self::$basePath.'/'.implode('/', func_get_args()));
    }

    /**
     * static function to fix paths '//' or '/./' or '/foo/../' in a path
     *
     * @param string $path Path to resolve
     *
     * @return string
     */
    public static function fixPath($path)
    {
        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }

    /**
     * Script executed by composer on install/update to generate the default environment variables
     *
     * @param Event $event The event object
     */
    public static function composerEvent(Event $event)
    {
        if (!is_file('env.local.php') || in_array('--force', $event->getArguments())) {
            $io = $event->getIO();
            $variables = require 'env.php';

            foreach ($variables as $name => &$value) {
                $value = $io->ask("Define: {$name} ({$value})", $value);
            }

            file_put_contents('env.local.php', "<?php\n\nreturn ".var_export($variables, true).';');
        }
    }
}
