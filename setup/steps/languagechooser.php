<?php
session_unset();

class cSetupLanguageChooser extends cSetupMask
{
	function cSetupLanguageChooser ()
	{
		cSetupMask::cSetupMask("templates/languagechooser.tpl");
		$this->setHeader("Contenido Setup Contenido " . C_SETUP_VERSION);
		$this->_oStepTemplate->set("s", "DE_HINT", "Diese Anwendung hilft Ihnen bei der Installation von Contenido.");
		$this->_oStepTemplate->set("s", "EN_HINT", "This application will guide you trough the setup process.");
		$this->_oStepTemplate->set("s", "DE_HINT_LANG", "W&auml;hlen Sie bitte die gew&uuml;nschte Sprache f&uuml;r das Setup aus.");
		$this->_oStepTemplate->set("s", "EN_HINT_LANG", "Please choose your language to continue.");
		
		$langs = array("de_DE" => "Deutsch", "C" => "English");
		
		$m = "";
		
		foreach ($langs as $entity => $lang)
		{
			$test = new cHTMLLanguageLink($entity, $lang, "setuptype");
			$m .= $test->render();
		}
		
		$this->_oStepTemplate->set("s", "LANGUAGECHOOSER", $m);
	}
}

$cSetupStep1 = new cSetupLanguageChooser;
$cSetupStep1->render();


?>