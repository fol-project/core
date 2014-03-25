<?php
/**
 * Fol\Http\Files
 *
 * Class to store the files variables ($_FILES)
 */
namespace Fol\Http;

class Files extends Input
{
    public static $errors = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder'
    ];


    /**
     * Detects global $_FILES array and retuns it
     * Fix the $files order by converting from default wierd schema
     * [first][name][second][0], [first][error][second][0]...
     * to a more straightforward one.
     * [first][second][0][name], [first][second][0][error]...
     *
     * @return array The files values fixed
     */
    public static function getFromGlobals()
    {
        if (empty($_FILES)) {
            return [];
        }

        return self::fixArray($_FILES);
    }


    /**
     * Fix the $files order by converting from default wierd schema
     * [first][name][second][0], [first][error][second][0]...
     * to a more straightforward one.
     * [first][second][0][name], [first][second][0][error]...
     *
     * @param array $files An array with all files values
     *
     * @return array The files values fixed
     */
    private static function fixArray($files)
    {
        if (isset($files['name'], $files['tmp_name'], $files['size'], $files['type'], $files['error'])) {
            return self::moveToRight($files);
        }

        foreach ($files as &$file) {
            $file = self::fixArray($file);
        }

        return $files;
    }


    /**
     * Private function used by fixArray
     *
     * @param array $files An array with all files values
     *
     * @return array The files values fixed
     */
    private static function moveToRight($files)
    {
        if (!is_array($files['name'])) {
            return $files;
        }

        $results = array();

        foreach ($files['name'] as $index => $name) {
            $reordered = array(
                'name' => $files['name'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'size' => $files['size'][$index],
                'type' => $files['type'][$index],
                'error' => $files['error'][$index]
            );

            if (is_array($name)) {
                $reordered = self::moveToRight($reordered);
            }

            $results[$index] = $reordered;
        }

        return $results;
    }



    /**
     * Check if an uploaded file has any error
     *
     * @param string $name The name of the uploaded file
     *
     * @return boolean True if has an error, false if not
     */
    public function hasError($name)
    {
        $file = $this->get($name);

        if (isset($file['error']) && $file['error'] > 0) {
            return true;
        }

        return false;
    }


    /**
     * Returns the error code
     *
     * @param string $name The name of the uploaded file
     *
     * @return int The error code or null if the file doesn't exist
     */
    public function getErrorCode($name)
    {
        $file = $this->get($name);

        if (isset($file['error'])) {
            return $file['error'];
        }

        return null;
    }


    /**
     * Returns the error message
     *
     * @param string $name The name of the uploaded file
     *
     * @return string The error message or null if the file doesn't exist
     */
    public function getErrorMessage($name)
    {
        $code = $this->getErrorCode($name);

        if (($code === null) || !isset(self::$errors[$code])) {
            return null;
        }

        return self::$errors[$code];
    }
}
