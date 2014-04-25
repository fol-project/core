<?php
/**
 * Fol\Terminal
 *
 * This is a simple class to execute system commands
 */
namespace Fol;

class Terminal
{
    /**
     * Executes a method from cli
     * 
     * @param array $options The arguments passed
     * 
     * @return mixed The value returned by the method
     */
    public static function executeFromCli (array $options)
    {
        //Removes file argument
        array_shift($options);

        $callable = [get_called_class(), array_shift($options)];

        if (!method_exists($callable[0], $callable[1])) {
            throw new \Exception("The method {$callable[0]}::{$callable[1]} does not exists");
        }

        $method = new \ReflectionMethod($callable[0], $callable[1]);
        $options = self::parseOptions($options);
        $parameters = [];

        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (isset($options[$name])) {
                $parameters[] = $options[$name];
                unset($options[$name]);
            } elseif (isset($options[0])) {
                $parameters[] = array_shift($options);
            } elseif ($parameter->isOptional()) {
                $parameters[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("The parameter '{$name}' is required");
            }
        }

        $parameters[] = $options;

        echo call_user_func_array($callable, $parameters);
    }


    /**
     * Parses an array of arguments ($argv) and returns the validated arguments
     *
     * @param array $options  The array of options to parse
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

        return $vars;
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
     * @param callable $callback Function executed for each update in the strout
     * @param string   $cwd      Working directory passed to proc_open
     * @param array    $env      Environment variables passed to proc_open
     * @param array    $options  Options passed to proc_open
     *
     * @return int The exit code of the process
     */
    public static function execute($command, callable $callback = null, $cwd = null, array $env = null, array $options = null)
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        if (($process = proc_open($command, $descriptorspec, $pipes, $cwd, $env, $options)) === false) {
            throw new \Exception("Error executing the command '$command'");
        }

        return self::executeProcess($process, $pipes, function ($stdout, $stderr, $status) {
            if (!empty($stdout)) {
                echo $stdout;
            } else if (!empty($stderr)) {
                echo "ERR: $stderr";
            }
        });
    }


    /**
     * Executes a process.
     *
     * @param resource $process  The process resource to execute
     * @param array    $pipes    Array with the file pointers to the process pipes
     * @param callable $callback Function executed for each update in the strout
     *
     * @return integer The exit code of the process
     */
    public static function executeProcess($process, array &$pipes, callable $callback)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title("FOL process");
        }

        $status = $errbuf = null;

        while (($buffer = fgets($pipes[1])) !== null || ($errbuf = fgets($pipes[2])) !== null) {
            $status = proc_get_status($process);

            if (($callback($buffer, $errbuf, $status) !== false) || ($status['running'] === false)) {
                break;
            }
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        if (!empty($status['running'])) {
            proc_terminate($process);

            return proc_close($process);
        }

        $return = proc_close($process);

        return strlen($status['exitcode']) ? $status['exitcode'] : $return;
    }
}
