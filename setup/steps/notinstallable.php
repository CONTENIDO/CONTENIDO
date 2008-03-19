<?php
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