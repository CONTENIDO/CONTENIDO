<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.CONTENIDO.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-02-23, Murat Purc, usage of new notinstallable template and several reason messages
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


session_unset();

class cSetupNotInstallable extends cSetupMask
{

    function cSetupNotInstallable ($sReason)
    {
        cSetupMask::cSetupMask("templates/notinstallable.tpl");
        $this->setHeader("CONTENIDO Version " . C_SETUP_VERSION);
        $this->_oStepTemplate->set("s", "TITLE", "Willkommen zu dem Setup von CONTENIDO / Welcome to the CONTENIDO Setup");
        $this->_oStepTemplate->set("s", "ERRORTEXT", "Setup nicht ausführbar / Setup not runnable");
        if ($sReason === 'session_use_cookies') {
            $this->_oStepTemplate->set("s", "REASONTEXT", "You need to set the PHP configuration directive 'session.use_cookies' to 1 and enable cookies in your browser. This setup won't work without that.");
        } elseif ($sReason === 'database_extension') {
            $this->_oStepTemplate->set("s", "REASONTEXT", "Couldn't detect neither MySQLi extension nor MySQL extension. You need to enable one of them in the PHP configuration (see dynamic extensions section in your php.ini). CONTENIDO won't work without that.");
        } elseif ($sReason === 'php_version') {
            $this->_oStepTemplate->set("s", "REASONTEXT", "Leider erfüllt Ihr Webserver nicht die Mindestvorraussetzung von PHP " . C_SETUP_MIN_PHP_VERSION . " oder höher. Bitte installieren Sie PHP " . C_SETUP_MIN_PHP_VERSION . " oder höher, um mit dem Setup fortzufahren.<br><br>Unfortunately your webserver doesn't match the minimum requirement of PHP " . C_SETUP_MIN_PHP_VERSION . " or higher. Please install PHP " . C_SETUP_MIN_PHP_VERSION . " or higher and then run the setup again.");
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