<?php
if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable
     * Converts the type of values like "true", "false" or "null"
     *
     * @param string $name The value name
     *
     * @return mixed
     */
    function env($name)
    {
        $value = getenv($name);

        switch (strtolower($value)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return;
        }

        return $value;
    }
}
