<?php
 /**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


session_unset();

class cSetupNotInstallable extends cSetupMask
{
	function cSetupNotInstallable ()
	{
		cSetupMask::cSetupMask("templates/languagechooser.tpl");
		$this->setHeader("Willkommen zu dem Setup von Contenido / Welcome to the Contenido Setup");
		$this->_oStepTemplate->set("s", "TITLE", "Setup nicht ausführbar / Setup not runnable");
		$this->_oStepTemplate->set("s", "WELCOMEMESSAGE", "Contenido Version " . C_SETUP_VERSION);
		$this->_oStepTemplate->set("s", "WELCOMETEXT", "Leider erfüllt Ihr Webserver nicht die Mindestvorraussetzung von PHP 4.1.0 oder höher. Bitte installieren Sie PHP4.1.0 oder höher, um mit dem Setup fortzufahren.");
		$this->_oStepTemplate->set("s", "ACTIONTEXT", "Unfortunately your webserver doesn't match the minimum requirement of PHP 4.1.0 or higher. Please install PHP 4.1.0 or higher and then run the setup again.");
		
		$langs = array("de_DE" => "Deutsch", "C" => "English");
		
		$this->_oStepTemplate->set("s", "LANGUAGECHOOSER", "");
	}
}

$cNotInstallable = new cSetupNotInstallable;
$cNotInstallable->render();


?>