<?php
/**
 * Fol\Fol
 *
 * Manages global data
 */

namespace Fol;

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
    public static function init($basePath, $env_file = 'constants.local.php')
    {
        self::$basePath = self::fixPath(str_replace('\\', '/', $basePath));

        self::$services['composer'] = require self::getPath('vendor/autoload.php');

        //Environment variables
        if ($env_file) {
            $constants = require self::getPath('constants.local.php');
        } else {
            $constants = [];
        }

        if (empty($constants['BASE_URL']) || php_sapi_name() === 'cli-server') {
            $constants['BASE_URL'] = ($_SERVER['HTTPS'] === 'on' ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].':';

            if (!empty($_SERVER['X_FORWARDED_PORT'])) {
                $constants['BASE_URL'] .= $_SERVER['X_FORWARDED_PORT'];
            } elseif (!empty($_SERVER['SERVER_PORT'])) {
                $constants['BASE_URL'] .= $_SERVER['SERVER_PORT'];
            } else {
                $constants['BASE_URL'] .= 80;
            }
        }

        self::$variables = $constants;
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
}
