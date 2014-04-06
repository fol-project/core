<?php
/**
 * Fol\Templates
 *
 * A simple class to manage template files
 */
namespace Fol;

class Templates
{
    private $sections = [];
    private $wrapper;
    private $functions = [];

    protected $renders = [];
    protected $templates = [];
    protected $templatesPaths = [];
    protected $currentPath;

    /**
     * Constructor method. You must define the base folder where the templates file are stored
     *
     * @param string/array $paths The base folder paths
     */
    public function __construct($paths)
    {
        $this->addFolders($paths);
    }

    /**
     * Adds new base folders where search for the templates files
     *
     * @param string/array $paths   The base folder paths
     * @param boolean      $prepend If it's true, insert the new folder at begining of the array.
     */
    public function addFolders($paths, $prepend = true)
    {
        $paths = (array) $paths;

        if ($prepend === true) {
            $this->templatesPaths = array_merge($paths, $this->templatesPaths);
        } else {
            $this->templatesPaths = array_merge($this->templatesPaths, $paths);
        }
    }


    /**
     * Register a new template file with a name
     * You can define an array of name => file
     *
     * @param string     $name       The template name (for example: menu)
     * @param string     $file       The file path of the template (for example: menu.php)
     * @param bool/array $data       Set null or an array to save the rendered file
     * @param bool       $failSilent Set true to do not throw the exception if the template does not exists
     */
    public function register($name, $file = null, $data = false, $failSilent = false)
    {
        $this->templates[$name] = isset($this->templates[$file]) ? $this->templates[$file] : $file;

        if ($data !== false) {
            $this->saveRender($name, $this->render($name, $data, $failSilent));
        }
    }


    /**
     * Save a rendered code to use it later
     *
     * @param string $name   The render name (for example: menu)
     * @param string $render The rendered code
     */
    public function saveRender($name, $render)
    {
        $this->renders[$name] = $render;
    }


    /**
     * Load an object as extension
     *
     * @param Object $class Any object with public methods
     */
    public function loadExtension($class)
    {
        if (!is_object($class)) {
            throw new \InvalidArgumentException("The extension must be an object");
        }

        $class->templates = $this;

        foreach (get_class_methods($class) as $name) {
            $this->functions[$name] = [$class, $name];
        }
    }


    /**
     * Magic method to execute the extensions functions
     */
    public function __call($name, $arguments)
    {
        if (!isset($this->functions[$name])) {
            throw new \LogicException("This extension function does not exist");
        }

        $function = $this->functions[$name];

        return call_user_func_array($function, $arguments);
    }


    /**
     * Init a new section capture
     *
     * @param string $name Section name
     */
    public function start($name)
    {
        $this->sections[] = $name;
        ob_start();
    }


    /**
     * Stops and save the latest section capture
     */
    public function end()
    {
        if (empty($this->sections)) {
            throw new \LogicException('You must start a section before end it.');
        }

        $name = array_pop($this->sections);

        $this->renders[$name] = ob_get_clean();
    }


    /**
     * Define a wrapper for the current template
     *
     * @param string $template  Template or file name
     * @param string $childName Name used in the wrapper to render the current template
     */
    public function wrapper($template, $data = null, $childName = 'content')
    {
        return $this->wrapper = [$template, $data, $childName];
    }


    /**
     * Gets a template file by name or filename
     *
     * $templates->file('menu');
     * $templates->file('menu.php');
     *
     * @param string $template The template name or file
     *
     * Returns string The template file path or false if does not exists
     */
    public function file($template)
    {
        if (isset($this->templates[$template])) {
            $template = $this->templates[$template];
        }

        if ($template[0] !== '/') {
            $template = "/$template";

            if (!empty($this->currentPath) && is_file($this->currentPath.$template)) {
                return $this->currentPath.$template;
            }
        }

        foreach ($this->templatesPaths as $path) {
            if (is_file($path.$template)) {
                return $path.$template;
            }
        }

        return false;
    }


    /**
     * Private function that renders a template file and returns its content
     *
     * @param string $file The template file
     * @param array  $data An array of variables used locally in the template.
     *
     * @return string The file content
     */
    protected function renderTemplateFile($file, array $data = null)
    {
        $previousPath = $this->currentPath;
        $previousWrapper = $this->wrapper;

        $this->currentPath = dirname($file);
        $this->wrapper = null;

        $content = $this->renderFile($file, $data);

        if ($this->wrapper) {
            list($wrapper, $dataWrapper, $childName) = $this->wrapper;

            $previousContent = isset($this->renders[$childName]) ? $this->renders[$childName] : null;
            $this->renders[$childName] = $content;
            $content = $this->render($wrapper, $dataWrapper);
            $this->renders[$childName] = $previousContent;
        }

        $this->currentPath = $previousPath;
        $this->wrapper = $previousWrapper;

        return $content;
    }


    /**
     * Render a file and returns its content
     *
     * @param string $_file The file path
     * @param array  $_data An array of variables used locally in the file.
     *
     * @return string
     */
    protected function renderFile($_file, array $_data = null)
    {
        if ($_data !== null) {
            extract($_data, EXTR_SKIP);
        }

        unset($_data);

        ob_start();

        include($_file);

        return ob_get_clean();
    }


    /**
     * Render a template and return its content
     *
     * @param string                           $template   The template name or file path
     * @param array/Iterator/IteratorAggregate $data       An optional array of object extending Iterator/IteratorAggregate data used in the template. If the array is numerical or the object extends Iterator/IteratorAggregate interfaces, renders the template once for each item
     * @param bool                             $failSilent Set true to do not throw the exception if the template does not exists
     *
     * @return string The template rendered
     */
    public function render($template, $data = null, $failSilent = false)
    {
        if (($data === null) && isset($this->renders[$template])) {
            return $this->renders[$template];
        }

        if (($file = $this->file($template)) === false) {
            if ($failSilent === true) {
                return '';
            }

            throw new \InvalidArgumentException("The template $template does not exists");
        }

        if (($data !== null) && static::isIterable($data)) {
            $result = '';

            foreach ($data as $value) {
                $result .= $this->renderTemplateFile($file, $value);
            }

            return $result;
        }

        return $this->renderTemplateFile($file, $data);
    }

    /**
     * Simple method to detect if a value must be iterabled or not
     */
    protected static function isIterable($data)
    {
        if (is_array($data)) {
            return (empty($data) || isset($data[0]));
        }

        if (($data instanceof \Iterator) || ($data instanceof \IteratorAggregate)) {
            return true;
        }

        return false;
    }
}
