<?php
/**
 * Fol\Filesystem
 * 
 * Simple class to manage files and folders
 */
namespace Fol;

class FileSystem {
	private $path;

	/**
	 * static function to resolve '//' or '/./' or '/foo/../' in a path
	 * 
	 * @param  string $path Path to resolve
	 * 
	 * @return string
	 */
	public static function fixPath ($path) {
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
	public function __construct ($path = null) {
		$this->path = BASE_PATH;

		if ($path !== null) {
			$this->moveTo($path);
		}
	}


	/**
	 * Returns the current path or a relative path
	 * 
	 * @param  string $path The relative path. If it's not defined, returns the current path
	 * 
	 * @return string
	 */
	public function getPath ($path = null) {
		if (empty($path)) {
			return $this->path;
		}

		if ($path[0] !== '/') {
			$path = "/$path";
		}

		return self::fixPath($this->path.$path);
	}


	/**
	 * Open a file and returns a splFileObject instance.
	 * 
	 * @param  string $path The file path (relative to the current path)
	 * @param  string $openMode The open mode. See fopen function to get all available modes
	 * 
	 * @return SplFileObject
	 * 
	 * @see  SplFileObject class
	 */
	public function openFile ($path, $openMode = 'r') {
		return new \SplFileObject($this->getPath($path));
	}


	/**
	 * Move the base path to other position
	 * 
	 * @param string $path Relative path with the new position
	 * 
	 * @return $this
	 */
	public function moveTo ($path) {
		$this->path = $this->getPath($path);

		return $this;
	}


	/**
	 * Returns a recursive iterator to explore all directories and subdirectories
	 * 
	 * @return RecursiveIteratorIterator
	 */
	public function getRecursiveIterator () {
		return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
	}


	/**
	 * Returns an iterator to explore the current path
	 * 
	 * @return FilesystemIterator
	 */
	public function getIterator () {
		return new \FilesystemIterator($this->path);
	}


	/**
	 * Remove all files and subdirectories of the current path
	 * 
	 * @return $this
	 */
	public function clear () {
		foreach ($this->getRecursiveIterator() as $file) {
			if ($file->isDir()) {
				rmdir($file->getPathname());
			} else {
				unlink($file->getPathname());
			}
		}

		return $this;
	}


	/**
	 * Delete the current path and all its content
	 * 
	 * @return $this
	 */
	public function delete () {
		$this->clear();

		rmdir($this->path);

		return $this;
	}


	/**
	 * Creates a new directory
	 * 
	 * @param  string  $name Directory name. If it's not specified, use the current defined path
	 * @param  integer $mode Permissions assigned to the directory
	 * @param  boolean $recursive Creates the directory in recursive mode or not. True by default
	 * 
	 * @return $this
	 */
	public function createDirectory ($name = '', $mode = 0777, $recursive = true) {
		$path = $this->getPath($name);

		if (!is_dir($path)) {
			mkdir($this->getPath($name), $mode, $recursive);
		}

		return $this;
	}


	/**
	 * Copy a file
	 * 
	 * @param  mixed $original The original file. It can be an array (from $_FILES), an url, a base64 file or a path
	 * @param  string $name The name of the created file. If it's not specified, use the same name of the original file. For base64 files, this parameter is required.
	 * 
	 * @return string The created filename or false if there was an error
	 */
	public function copy ($original, $name = null) {
		if (is_array($original)) {
			return $this->saveFromUpload($original, $name);
		}

		if (substr($original, 0, 5) === 'data:') {
			return $this->saveFromBase64($original, $name);
		}

		if (strpos($original, '://')) {
			return $this->saveFromUrl($original, $name);
		}

		return @copy($original, $this->getPath($name)) ? $name : false;
	}


	/**
	 * Private function to save a file from upload ($_FILES)
	 * 
	 * @param  array $original Original file data
	 * @param  string $name Name used for the new file
	 * 
	 * @return string The created filename or false if there was an error
	 */
	private function saveFromUpload (array $original, $name) {
		if (empty($input['tmp_name']) || !empty($input['error'])) {
			return false;
		}

		if ($name === null) {
			$name = $original['name'];
		} elseif (!pathinfo($name, PATHINFO_EXTENSION) && ($extension = pathinfo($original['name'], PATHINFO_EXTENSION))) {
			$name .= ".$extension";
		}

		return @rename($original, $this->getPath($name)) ? $name : false;
	}


	/**
	 * Private function to save a file from base64 string
	 * 
	 * @param  array $original Original data
	 * @param  string $name Name used for the new file
	 * 
	 * @return string The created filename or false if there was an error
	 */
	private function saveFromBase64 ($original, $name) {
		if (empty($name)) {
			throw new \Exception("The argument 'name' is required for base64 saving files");
		}

		$fileData = explode(';base64,', $original, 2);

		if (!pathinfo($name, PATHINFO_EXTENSION) && preg_match('|data:\w+/(\w+)|', $fileData[0], $match)) {
			$name .= '.'.$match[1];
		}

		return @file_put_contents($this->getPath($name), base64_decode($fileData[1])) ? $name : false;
	}


	/**
	 * Private function to save a file from an url
	 * 
	 * @param  array $original Original file url
	 * @param  string $name Name used for the new file
	 * 
	 * @return string The created filename or false if there was an error
	 */
	private function saveFromUrl ($original, $name) {
		if ($name === null) {
			$name = pathinfo($original, PATHINFO_BASENAME);
		} else if (!pathinfo($name, PATHINFO_EXTENSION) && ($extension = pathinfo(parse_url($original, PHP_URL_PATH), PATHINFO_EXTENSION))) {
			$name .= ".$extension";
		}

		return (($content = @file_get_contents($original)) && @file_put_contents($this->getPath($name), $content)) ? $name : false;
	}
}
