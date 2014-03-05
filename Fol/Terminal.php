<?php
namespace Fol;

class Terminal {
	const OPTION_BOOLEAN = 1;
	const OPTION_REQUIRED = 2;
	const OPTION_OPTIONAL = 3;

	public static function parseOptions (array $options, array $validator = null) {
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
				if (is_array($property)) {
					if (!isset($vars[$name]) || !in_array($vars[$name], $property)) {
						throw new \Exception("The option '$name' must be one of these values: ".implode(', ', $property));
					}

					continue;
				}

				switch ($property) {
					case self::OPTION_BOOLEAN:
						if (isset($vars[$name]) && ($vars[$name] !== true)) {
							throw new \Exception("The option '$name' does not accept values");
						}
						break;

					case self::OPTION_REQUIRED:
						if (empty($vars[$name])) {
							throw new \Exception("The option '$name' is required");
						}
						break;

					case self::OPTION_OPTIONAL:
						if (isset($vars[$name]) && ($vars[$name] === true)) {
							throw new \Exception("The option '$name' must have a value");
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



	public static function execute ($command, $cwd = null) {
		if ($cwd === null) {
			$cwd = BASE_PATH;
		}

		$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);

		if (($process = proc_open($command, $descriptorspec, $pipes, $cwd)) === false) {
			throw new \Exception("Error executing the command '$command'");
		}

		$buffer = $errbuf = '';

		while (($buffer = fgets($pipes[1])) !== null || ($errbuf = fgets($pipes[2])) !== null) {
			$status = proc_get_status($process);

			if (strlen($buffer)) {
				echo $buffer;
			}

			if (strlen($errbuf)) {
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
