<?php
use Fol\Container\Container;

/**
 * Manages global data
 */
class Fol
{
    private static $container;
    private static $basePath;

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
