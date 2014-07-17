<?php
/**
 * Fol\Filesystem
 *
 * Simple class to manage files and folders
 */
namespace Fol;

class FileSystem
{
    private $path;


    /**
     * static function to check whether a path is absolute or not
     *
     * @param string $path Path to check
     *
     * @return boolean
     */
    public static function isAbsolute($path)
    {
        return ($path[0] === '/' || preg_match('|^\w:/|', $path));
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
        if (func_num_args() > 1) {
            return static::fixPath(implode('/', func_get_args()));
        }

        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }


    /**
     * Constructor
     *
     * @param string $path Base path
     */
    public function __construct($path = null)
    {
        if ($path !== null) {
            $this->cd($path);
        }
    }


    /**
     * Returns the current path or a relative path
     *
     * @param null|string $path The relative path. If it's not defined, returns the current path
     *
     * @return string
     */
    public function getPath($path = null)
    {
        if (empty($path)) {
            return $this->path;
        }

        if (static::isAbsolute($path)) {
            return static::fixPath($path);
        }

        return static::fixPath($this->path, $path);
    }

    /**
     * Open a file and returns a splFileObject instance.
     *
     * @param null|string $path     The file path (relative to the current path)
     * @param string      $openMode The open mode. See fopen function to get all available modes
     *
     * @return \SplFileObject
     *
     * @see  SplFileObject class
     */
    public function openFile($path = null, $openMode = 'r')
    {
        return new \SplFileObject($this->getPath($path), $openMode);
    }

    /**
     * Returns a SplFileInfo instance to access to the file info
     *
     * @param null|string $path The file path (relative to the current path)
     *
     * @return \SplFileInfo
     *
     * @see  SplFileInfo class
     */
    public function getInfo($path = null)
    {
        return new \SplFileInfo($this->getPath($path));
    }

    /**
     * Change the current directory
     *
     * @param string $path   Relative path with the new position
     * @param bool   $create Create the directory if doesn't exist
     *
     * @return $this
     */
    public function cd($path, $create = false)
    {
        $this->path = $this->getPath($path);

        if ($create === true) {
            $this->mkdir();
        }

        return $this;
    }


    /**
     * Returns a recursive iterator to explore all directories and subdirectories
     *
     * @param null|string $path Relative path with the new position
     *
     * @return \RecursiveIteratorIterator
     */
    public function getRecursiveIterator($path = null)
    {
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getPath($path), \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
    }


    /**
     * Returns an iterator to explore the current path
     *
     * @param null|string  $path  Relative path with the new position
     * @param null|integer $flags Flags constants passed to the FilesystemIterator
     *
     * @see FilesystemIterator
     *
     * @return \FilesystemIterator
     */
    public function getIterator($path = null, $flags = null)
    {
        $path = $this->getPath($path);

        if ($flags === null) {
            return new \FilesystemIterator($path);
        }

        return new \FilesystemIterator($path, $flags);
    }


    /**
     * Returns a glob iterator to explore the current path
     *
     * @param null|string  $path  Relative path with the new position
     * @param null|integer $flags Flags constants passed to the GlobIterator
     *
     * @see GlobIterator
     *
     * @return \GlobIterator
     */
    public function getGlobIterator($path = null, $flags = null)
    {
        $path = $this->getPath($path);

        if ($flags === null) {
            return new \GlobIterator($path);
        }

        return new \GlobIterator($path, $flags);
    }


    /**
     * Remove all files and subdirectories of the current path
     *
     * @param null|string $path Relative path with the new position
     *
     * @return $this
     */
    public function clear($path = null)
    {
        if (!$this->getInfo($path)->isDir()) {
            return $this;
        }

        foreach ($this->getRecursiveIterator($path) as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        return $this;
    }


    /**
     * Remove the current path and all its content
     *
     * @param null|string $path Relative path with the new position
     *
     * @return $this
     */
    public function remove($path = null)
    {
        if ($this->getInfo($path)->isDir()) {
            $this->clear($path);

            rmdir($this->getPath($path));
        } else {
            unlink($this->getPath($path));
        }

        return $this;
    }


    /**
     * Creates a new directory
     *
     * @param null|string $name      Directory name. If it's not specified, use the current defined path
     * @param integer     $mode      Permissions assigned to the directory
     * @param boolean     $recursive Creates the directory in recursive mode or not. True by default
     *
     * @return $this
     */
    public function mkdir($name = null, $mode = 0777, $recursive = true)
    {
        $path = $this->getPath($name);

        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
        }

        return $this;
    }

    /**
     * Copy a file
     *
     * @param mixed       $original The original file. It can be an array (from $_FILES), an url, a base64 file or a path
     * @param null|string $name     The name of the created file. If it's not specified, use the same name of the original file. For base64 files, this parameter is required.
     *
     * @throws \Exception On error
     *
     * @return \SplFileInfo The created filename
     */
    public function copy($original, $name = null)
    {
        if (is_array($original)) {
            return $this->saveFromUpload($original, $name);
        }

        if (substr($original, 0, 5) === 'data:') {
            return $this->saveFromBase64($original, $name);
        }

        if (strpos($original, '://')) {
            return $this->saveFromUrl($original, $name);
        }

        $destination = $this->getDestination($original, $name);

        if (!@copy($original, $destination)) {
            throw new \Exception("Unable to copy '$original' to '$destination'");
        }

        return new \SplFileInfo($destination);
    }


    /**
     * Private function to save a file from upload ($_FILES)
     *
     * @param array       $original Original file data
     * @param null|string $name     Name used for the new file
     *
     * @throws \Exception On error
     *
     * @return \SplFileInfo The created filename
     */
    private function saveFromUpload(array $original, $name = null)
    {
        if (empty($original['tmp_name']) || !empty($original['error'])) {
            throw new \Exception("Unable to copy the uploaded file because has an error");
        }

        $destination = $this->getDestination($original['name'], $name);

        if (!@rename($original['tmp_name'], $destination)) {
            throw new \Exception("Unable to copy '$original' to '$destination'");
        }

        return new \SplFileInfo($destination);
    }


    /**
     * Private function to save a file from base64 string
     *
     * @param array  $original Original data
     * @param string $name     Name used for the new file
     *
     * @throws \Exception On error
     *
     * @return \SplFileInfo The created filename
     */
    private function saveFromBase64($original, $name = null)
    {
        if (!$name) {
            $name = uniqid();
        }

        $fileData = explode(';base64,', $original, 2);

        if (!pathinfo($name, PATHINFO_EXTENSION) && preg_match('|data:\w+/(\w+)|', $fileData[0], $match)) {
            $name .= '.'.$match[1];
        }

        $destination = $this->getDestination(null, $name);

        if (!@file_put_contents($destination, base64_decode($fileData[1]))) {
            throw new \Exception("Unable to copy base64 to '$destination'");
        }

        return new \SplFileInfo($destination);
    }


    /**
     * Private function to save a file from an url
     *
     * @param string      $original Original file url
     * @param null|string $name     Name used for the new file
     *
     * @throws \Exception On error
     *
     * @return \SplFileInfo The created filename
     */
    private function saveFromUrl($original, $name = null)
    {
        $destination = $this->getDestination($original, $name);

        if (!($content = @file_get_contents($original)) || !@file_put_contents($destination, $content)) {
            throw new \Exception("Unable to copy '$original' to '$destination'");
        }

        return new \SplFileInfo($destination);
    }


    /**
     * Gets the destination filename before save it
     *
     * @param string $oldFilename The original filename
     * @param string $newFilename The destination filename
     *
     * @return string
     */
    private function getDestination($oldFilename, $newFilename)
    {
        if ($newFilename === null) {
            if ($oldFilename === null) {
                return $this->getPath(uniqid());
            }

            return $this->getPath($oldFilename);
        }

        $destination = $this->getPath($newFilename);

        if (is_dir($destination)) {
            return self::fixPath($destination, $oldFilename);
        }

        if (!pathinfo($destination, PATHINFO_EXTENSION) && ($path = parse_url($oldFilename, PHP_URL_PATH)) && ($extension = pathinfo($path, PATHINFO_EXTENSION))) {
            $destination .= ".$extension";
        }

        return $destination;
    }
}
