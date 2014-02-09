<?php
/**
 * Fol\Composer
 * 
 * Class to execute composer scripts on install/update Fol
 */
namespace Fol;

use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Composer\IO\IOInterface;

class Composer {

	/**
	 * Define the environment variables (for example the constants ENVIRONMET and BASE_URL)
	 *
	 * @param Composer\IO\IOInterface $io The IO class to ask the questions
	 */
	private static function setEnvironment (IOInterface $io) {
		$defaults = require 'environment.php';
		$environment = [];

		foreach ($defaults as $name => $default) {
			$environment[$name] = $io->ask("Config > {$name} = '{$default}' ?: ", $default);
		}

		file_put_contents('environment.php', "<?php\n\nreturn ".var_export($environment, true).';');
	}


	/**
	 * Define the custom settings for this installation (Files defined in extras->config in the composer.json)
	 *
	 * @param Composer\Package\PackageInterface $package The installed package
	 * @param Composer\IO\IOInterface $io The IO class to ask the questions
	 */
	private static function setConfig (PackageInterface $package, IOInterface $io) {
		$extra = $package->getExtra();

		if (empty($extra['config'])) {
			return;
		}

		$environment = require 'environment.php';
		$environment = $environment['ENVIRONMENT'];

		foreach ($extra['config'] as $configFile) {
			$dir = dirname($configFile).'/';
			$envDir = "$dir/$environment/";
			$base = basename($configFile, '.php');

			$defaults = require(is_file("{$envDir}{$base}.php") ? "{$envDir}{$base}.php" : "{$dir}{$base}.php");
			$config = [];

			foreach ($defaults as $name => $default) {
				$config[$name] = $io->ask("Config > {$base}.{$name} = '{$default}' ?: ", $default);
			}

			if (!is_dir($envDir)) {
				mkdir($envDir, 0777, true);
			}

			file_put_contents("{$envDir}{$base}.php", "<?php\n\nreturn ".var_export($config, true).';');
		}
	}


	/**
	 * Script executed by composer on post-create-project-cmd event
	 *
	 * @param Composer\Script\Event $event The event object
	 */
	public static function postCreateProject (Event $event) {
		self::setEnvironment($event->getIO());
		self::setConfig($event->getComposer()->getPackage(), $event->getIO());
	}
}
