<?php

class cSetupClientMode extends cSetupMask
{
	function cSetupClientMode ($step, $previous, $next)
	{
		cSetupMask::cSetupMask("templates/setup/forms/clientmode.tpl", $step);
		$this->setHeader(i18n("Example Client"));
		$this->_oStepTemplate->set("s", "TITLE", i18n("Example Client"));
		$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("If you are new to Contenido, you should create an example client to start working with."));

		cInitializeArrayKey($_SESSION, "clientmode", "");
		
		$aChoices = array(	"CLIENTEXAMPLES" => i18n("Client with example modules and example content"),
							"CLIENTMODULES"  => i18n("Client with example modules, but without example content"),
							"CLIENT"		 => i18n("Client without examples"),
							"NOCLIENT"		 => i18n("Don't create client"));
							
		foreach ($aChoices as $sKey => $sChoice)
		{
			$oRadio = new cHTMLRadiobutton("clientmode", $sKey);
			$oRadio->setLabelText(" ");
			$oRadio->setStyle('width:auto;border:0;');
			
			if ($_SESSION["clientmode"] == $sKey || ($_SESSION["clientmode"] == "" && $sKey == "CLIENTEXAMPLES"))
			{
				$oRadio->setChecked("checked");	
			}			
			
			$oLabel = new cHTMLLabel($sChoice, $oRadio->getId());
			
			$this->_oStepTemplate->set("s", "CONTROL_".$sKey, $oRadio->render());
			$this->_oStepTemplate->set("s", "LABEL_".$sKey, $oLabel->render());
		} 

		$this->setNavigation($previous, $next);
	}
		
}

?>