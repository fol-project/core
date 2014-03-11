<?php
/**
 * Fol\Terminal
 *
 * This is a simple class to execute system commands
 */
namespace Fol;

class Terminal
{
    const OPTION_BOOLEAN = 1;
    const OPTION_REQUIRED = 2;
    const OPTION_OPTIONAL = 3;
    const OPTION_SET = 4;


    /**
     * Parses an array of arguments ($argv) and returns the validated arguments
     *
     * @param array $options   The array of options to parse
     * @param array $validator An optional array to validate data. Each key (option name) must have one of the OPTION_* constant
     *
     * @return array An array with two subarrays: the numeric options and named options
     */
    public static function parseOptions(array $options, array $validator = null)
    {
        $vars = [];

        for ($k = 0, $total = count($options); $k < $total; $k++) {
            $option = $options[$k];

            if (preg_match('#^--([\w]+)(=[\'"]?(.*)[\'"]?)?$#', $option, $match)) {
                $name = $match[1];

                if (isset($match[3])) {
                    $vars[$name] = $match[3];
                    continue;
                }

                if (!empty($options[$k + 1]) && $options[$k + 1][0] !== '-' && (!isset($validator[$name]) || $validator[$name] !== self::OPTION_BOOLEAN)) {
                    $vars[$name] = $options[++$k];
                    continue;
                }

                $vars[$match[1]] = null;
                continue;
            }

            if (preg_match('#^-([\w])$#', $option, $match)) {
                if (!empty($options[$k + 1]) && $options[$k + 1][0] !== '-') {
                    $vars[$match[1]] = $options[++$k];
                    continue;
                }

                $vars[$match[1]] = true;
                continue;
            }

            $vars[$k] = $option;
        }

        if ($validator !== null) {
            foreach ($validator as $name => $property) {
                $default = null;

                if (is_array($property)) {
                    list($property, $default) = $property;
                }

                switch ($property) {
                    case self::OPTION_BOOLEAN:
                        if (isset($vars[$name])) {
                            if ($vars[$name] !== true) {
                                throw new \Exception("The option '$name' does not accept values");
                            }
                        } else {
                            $vars[$name] = (bool)$default;
                        }
                        break;

                    case self::OPTION_REQUIRED:
                        if (empty($vars[$name])) {
                            throw new \Exception("The option '$name' is required");
                        }
                        break;

                    case self::OPTION_OPTIONAL:
                        if (isset($vars[$name])) {
                            if ($vars[$name] === true) {
                                throw new \Exception("The option '$name' must have a value");
                            }
                        } else {
                            $vars[$name] = $default;
                        }
                        break;

                    case self::OPTION_SET:
                        if (!isset($vars[$name]) || !in_array($vars[$name], $default)) {
                            throw new \Exception("The option '$name' must be one of these values: ".implode(', ', $default));
                        }

                        break;

                    default:
                        throw new \Exception("Option property for '$name' is not valid ($property)");
                }
            }
        }

        $numeric = [];
        $associative = [];

        foreach ($vars as $key => $value) {
            if (is_int($key)) {
                $numeric[$key] = $value;
            } else {
                $associative[$key] = $value;
            }
        }

        return [$numeric, $associative];
    }


    /**
     * Launch a question to the user and returns the answer
     *
     * @param string   $prompt   The string to the user
     * @param string   $default  The default value if the user send an empty value
     * @param callable $callback A function to validate/sanitize the input. If returns false, resend the prompt again
     *
     * @return string The user/default value
     */
    public static function prompt($prompt, $default = null, callable $callback = null)
    {
        while (true) {
            echo "$prompt ";

            $input = trim(fgets(STDIN));

            if ($callback !== null) {
                $input = $callback($input);

                if ($input === false) {
                    continue;
                }
            }

            return $input ?: $default;
        }
    }


    /**
     * Executes a command.
     *
     * @param string   $command  The command to execute
     * @param string   $cwd      Working directory passed to proc_open
     * @param callable $callback Function executed for each update in the strout
     * @param array    $env      Environment variables passed to proc_open
     * @param array    $options  Options passed to proc_open
     *
     * @return int The exit code of the proccess
     */
    public static function execute($command, $cwd = null, callable $callback = null, array $env = null, array $options = null)
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        if (($process = proc_open($command, $descriptorspec, $pipes, $cwd, $env, $options)) === false) {
            throw new \Exception("Error executing the command '$command'");
        }

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title("FOL process: $command");
        }

        $buffer = $errbuf = '';

        while (($buffer = fgets($pipes[1])) !== null || ($errbuf = fgets($pipes[2])) !== null) {
            $status = proc_get_status($process);

            if ($callback !== null) {
                $callback($buffer, $errbuf, $status);
            } elseif (strlen($buffer)) {
                echo $buffer;
            } elseif (strlen($errbuf)) {
                echo "ERR: " . $errbuf;
            }

            if ($status['running'] === false) {
                break;
            }
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        if ($status['running'] === true) {
            proc_terminate($process);

            return proc_close($process);
        }

        $return = proc_close($process);

        return strlen($status['exitcode']) ? $status['exitcode'] : $return;
    }
}
