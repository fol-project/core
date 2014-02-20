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
	 * Define the constants (for example the constants ENVIRONMET and BASE_URL)
	 *
	 * @param Composer\IO\IOInterface $io The IO class to ask the questions
	 */
	private static function setConstants (IOInterface $io) {
		$defaults = require 'constants.php';
		$constants = [];

		foreach ($defaults as $name => $default) {
			$constants[$name] = $io->ask("Constant > {$name} = '{$default}' > ", $default);
		}

		file_put_contents('constants.php', "<?php\n\nreturn ".var_export($constants, true).';');
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

		$environment = require 'constants.php';
		$environment = $environment['ENVIRONMENT'];

		foreach ($extra['config'] as $configFile) {
			$dir = dirname($configFile).'/';
			$envDir = "{$dir}{$environment}/";
			$base = basename($configFile, '.php');

			$defaults = require(is_file("{$envDir}{$base}.php") ? "{$envDir}{$base}.php" : "{$dir}{$base}.php");
			$config = [];

			foreach ($defaults as $name => $default) {
				$config[$name] = $io->ask("Config > {$base}.{$name} = '{$default}' > ", $default);
			}

			if (!is_dir($envDir)) {
				mkdir($envDir, 0777, true);
				$io->write("Creating writable (0777) configuration folder '$envDir'");
			}

			file_put_contents("{$envDir}{$base}.php", "<?php\n\nreturn ".var_export($config, true).';');
		}
	}


	/**
	 * Define the writable folder permissions
	 *
	 * @param Composer\Package\PackageInterface $package The installed package
	 * @param Composer\IO\IOInterface $io The IO class to ask the questions
	 */
	private static function setWritable (PackageInterface $package, IOInterface $io) {
		$extra = $package->getExtra();

		if (empty($extra['writable'])) {
			return;
		}

		foreach ($extra['writable'] as $dir) {
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
				$io->write("Creating writable (0777) folder '$dir'");
			} else if ((fileperms($dir) & 0777) !== 0777) {
				chmod($dir, 0777);
				$io->write("Making the folder '$dir' writable (0777)");
			}
		}
	}


	/**
	 * Script executed by composer on post-create-project-cmd event
	 *
	 * @param Composer\Script\Event $event The event object
	 */
	public static function postCreateProject (Event $event) {
		self::setConstants($event->getIO());
		self::setConfig($event->getComposer()->getPackage(), $event->getIO());
		self::setWritable($event->getComposer()->getPackage(), $event->getIO());
	}
}
