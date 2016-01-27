<?php
/**
 * This file contains the setup not installable class.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

session_unset();

/**
 * Setup not installable class
 *
 * @package Setup
 * @subpackage Setup
 */
class cSetupNotInstallable extends cSetupMask
{

    function cSetupNotInstallable ($sReason)
    {
        cSetupMask::cSetupMask("templates/notinstallable.tpl");
        $this->setHeader("CONTENIDO Version " . CON_SETUP_VERSION);
        $this->_oStepTemplate->set("s", "TITLE", "Willkommen zu dem Setup von CONTENIDO / Welcome to the CONTENIDO Setup");
        $this->_oStepTemplate->set("s", "ERRORTEXT", "Setup nicht ausf&uuml;hrbar / Setup not runnable");
        if ($sReason === 'session_use_cookies') {
            $this->_oStepTemplate->set("s", "REASONTEXT", "You need to set the PHP configuration directive 'session.use_cookies' to 1 and enable cookies in your browser. This setup won't work without that.");
        } elseif ($sReason === 'database_extension') {
            $this->_oStepTemplate->set("s", "REASONTEXT", "Couldn't detect neither MySQLi extension nor MySQL extension. You need to enable one of them in the PHP configuration (see dynamic extensions section in your php.ini). CONTENIDO won't work without that.");
        } elseif ($sReason === 'php_version') {
            $this->_oStepTemplate->set("s", "REASONTEXT", "Leider erf&uuml;llt Ihr Webserver nicht die Mindestvorraussetzung von PHP " . CON_SETUP_MIN_PHP_VERSION . " oder h&ouml;her. Bitte installieren Sie PHP " . CON_SETUP_MIN_PHP_VERSION . " oder h&ouml;her, um mit dem Setup fortzufahren.<br><br>Unfortunately your webserver doesn't match the minimum requirement of PHP " . CON_SETUP_MIN_PHP_VERSION . " or higher. Please install PHP " . CON_SETUP_MIN_PHP_VERSION . " or higher and then run the setup again.");
        } else {
            // this should not happen
            $this->_oStepTemplate->set("s", "REASONTEXT", "Reason unknown");
        }
    }
}

global $sNotInstallableReason;

$cNotInstallable = new cSetupNotInstallable($sNotInstallableReason);
$cNotInstallable->render();

die();

?>