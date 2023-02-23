<?php

/**
 * This file contains the setup not installable class.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

session_unset();

/**
 * Setup not installable class
 *
 * @package    Setup
 * @subpackage Setup
 */
class cSetupNotInstallable extends cSetupMask
{
    public function __construct($reason) {
        cSetupMask::__construct("templates/notinstallable.tpl");
        $this->setHeader("CONTENIDO Version " . CON_SETUP_VERSION);
        $this->_stepTemplateClass->set("s", "TITLE", "Willkommen zu dem Setup von CONTENIDO / Welcome to the CONTENIDO Setup");
        $this->_stepTemplateClass->set("s", "ERRORTEXT", "Setup nicht ausf&uuml;hrbar / Setup not runnable");
        if ($reason === 'session_use_cookies') {
            $this->_stepTemplateClass->set("s", "REASONTEXT", "You need to set the PHP configuration directive 'session.use_cookies' to 1 and enable cookies in your browser. This setup won't work without that.");
        } elseif ($reason === 'database_extension') {
            $this->_stepTemplateClass->set("s", "REASONTEXT", "Couldn't detect neither MySQLi extension nor MySQL extension. You need to enable one of them in the PHP configuration (see dynamic extensions section in your php.ini). CONTENIDO won't work without that.");
        } elseif ($reason === 'php_version') {
            $this->_stepTemplateClass->set("s", "REASONTEXT", "Leider erf&uuml;llt Ihr Webserver nicht die Mindestvoraussetzung von PHP " . CON_SETUP_MIN_PHP_VERSION . " oder h&ouml;her. Bitte installieren Sie PHP " . CON_SETUP_MIN_PHP_VERSION . " oder h&ouml;her, um mit dem Setup fortzufahren.<br><br>Unfortunately your webserver doesn't match the minimum requirement of PHP " . CON_SETUP_MIN_PHP_VERSION . " or higher. Please install PHP " . CON_SETUP_MIN_PHP_VERSION . " or higher and then run the setup again.");
        } else {
            // this should not happen
            $this->_stepTemplateClass->set("s", "REASONTEXT", "Reason unknown");
        }
    }


    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $reason
     */
    public function cSetupNotInstallable($reason) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($reason);
    }
}

global $sNotInstallableReason;

$cNotInstallable = new cSetupNotInstallable($sNotInstallableReason);
$cNotInstallable->render();

die();
