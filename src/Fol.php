<?php
/**
 * Fol\Fol.
 *
 * Manages global data
 */

namespace Fol;

class Fol
{
    private static $variables = [];
    private static $container;
    private static $basePath;

    /**
     * Init the fol basic operations.
     *
     * @param string $basePath The base path of the fol installation
     * @param string $env_file The file with the environment variables
     */
    public static function init($basePath, $env_file = 'env.php')
    {
        self::$basePath = self::fixPath(str_replace('\\', '/', $basePath));

        self::$container = new Container\Container();

        self::$container->set('composer', require self::getPath('vendor/autoload.php'));

        //Environment variables
        if ($env_file && is_file(self::getPath($env_file))) {
            $variables = require self::getPath($env_file);
        } else {
            $variables = [];
        }

        if (empty($variables['BASE_URL']) || php_sapi_name() === 'cli-server') {
            $variables['BASE_URL'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http').'://'.(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost').':';

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
     * Returns the global service container.
     *
     * @return Container\Container
     */
    public static function services()
    {
        return self::$container;
    }

    /**
     * Set an environmet variable.
     *
     * @param string $name
     * @param mixed  $value
     */
    public static function setEnv($name, $value)
    {
        self::$variables[$name] = $value;
    }

    /**
     * Get an environmet variable.
     *
     * @param string $name The name of the variable
     */
    public static function getEnv($name)
    {
        return isset(self::$variables[$name]) ? self::$variables[$name] : null;
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
