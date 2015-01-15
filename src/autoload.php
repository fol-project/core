<?php
function FolLoader($name)
{
    if (strpos($name, 'Fol\\') !== 0) {
        return;
    }

    $file = dirname(__DIR__).'/src/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($name, 4)).'.php';

    if (is_file($file)) {
        require $file;
    }
}

spl_autoload_register('FolLoader');
