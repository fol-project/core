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
     * Script executed by composer on some events
     *
     * @param Event $event The event object
     */
    public static function setConstants(Event $event)
    {
        if (!is_file('constants.local.php') || in_array('--force', $event->getArguments())) {
            $io = $event->getIO();
            $constants = require 'constants.php';

            foreach ($constants as $name => &$value) {
                $value = $io->ask("Constant > {$name} = '{$value}' > ", $value);
            }

            file_put_contents('constants.local.php', "<?php\n\nreturn ".var_export($constants, true).';');
        }
    }
}
