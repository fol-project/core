<?php
use Fol\Container\Container;

/**
 * Manages global data
 */
class Fol
{
    private static $container;
    private static $basePath;
    private static $env = [];

    /**
     * Init the fol basic operations.
     *
     * @param string $basePath The base path of the fol installation
     */
    public static function init($basePath)
    {
        self::$basePath = self::fixPath(str_replace('\\', '/', $basePath));

        self::$container = new Container();
        self::$container->set('composer', require self::getPath('vendor/autoload.php'));

        //Load env variables
        if (is_file(self::getPath('env.php'))) {
            self::$env = require self::getPath('env.php');
        }
    }

    /**
     * Init as remote fol installation
     *
     * @param string $basePath The base path of the fol installation
     */
    public static function initAsRemote($basePath)
    {
        $basePath = self::fixPath(str_replace('\\', '/', $basePath));

        require self::fixPath("{$basePath}/vendor/autoload.php");

        //Load .env variables
        $envFile = self::fixPath("{$basePath}/.env");

        if (is_file($envFile)) {
            self::$env += parse_ini_file($envFile);
        }
    }

    /**
     * Search for an environment variable in different places and returns the first found
     * 
     * @param string $name
     * 
     * @return string|null
     */
    public static function getEnv($name)
    {
        if (array_key_exists($name, self::$env)) {
            return self::$env[$name];
        }

        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }

        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }
        
        $value = getenv($name);

        return $value === false ? null : $value; // switch getenv default to null
    }

    /**
     * Magic method to use Fol like a container
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @throws \BadMethodCallException
     * 
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        if (method_exists(self::$container, $name)) {
            return call_user_func_array([self::$container, $name], $arguments);
        }

        throw new \BadMethodCallException("The method {$name} does not exists");
    }

    /**
     * Set the base path of the fol installation.
     *
     * @return string
     */
    public static function getPath()
    {
        return self::fixPath(self::$basePath.'/'.implode('/', func_get_args()));
    }

    /**
     * static function to fix paths '//' or '/./' or '/foo/../' in a path.
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
