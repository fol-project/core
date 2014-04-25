<?php
/**
 * Fol\Composer
 *
 * Class to execute composer scripts on install/update Fol
 */
namespace Fol;

use Composer\Script\Event;
use Composer\IO\IOInterface;

class Composer
{
    /**
     * Script executed by composer on post-create-project-cmd event
     *
     * @param Event $event The event object
     */
    public static function postCreateProject(Event $event)
    {
        self::setConstants($event->getIO());
    }

    /**
     * Define the constants (for example the constants ENVIRONMET and BASE_URL)
     *
     * @param IOInterface $io The IO class to ask the questions
     */
    private static function setConstants(IOInterface $io)
    {
        $file = 'constants.php';
        $constants = require $file;

        foreach ($constants as $name => &$value) {
            $value = $io->ask("Constant > {$name} = '{$value}' > ", $value);
        }

        file_put_contents($file, "<?php\n\nreturn ".var_export($constants, true).';');
    }
}
