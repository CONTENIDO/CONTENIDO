<?php

class cSetupSetupSummary extends cSetupMask
{
	function cSetupSetupSummary ($step, $previous, $next)
	{
		cSetupMask::cSetupMask("templates/setup/forms/setupsummary.tpl", $step);
		$this->setHeader(i18n("Summary"));
		$this->_oStepTemplate->set("s", "TITLE", i18n("Summary"));
		$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please check your settings and click on the next button to start the installation"));

		$cHTMLErrorMessageList = new cHTMLErrorMessageList;
		
		switch ($_SESSION["setuptype"])
		{
			case "setup":
				$sType = i18n("Setup"); 	
				break;
			case "upgrade":
				$sType = i18n("Upgrade");
				break;
			case "migration":
				$sType = i18n("Migration");
				break;
		}
		
		switch ($_SESSION["configmode"])
		{
			case "save":
				$sConfigMode = i18n("Save");
				break;
			case "download":
				$sConfigMode = i18n("Download");
				break;
		}
		$messages = array(i18n("Installation type").":" => $sType,
						  i18n("Database parameters").":" => i18n("Database host").": ".$_SESSION["dbhost"] . "<br>" . i18n("Database name").": ".$_SESSION["dbname"] ."<br>".i18n("Database username").": " . $_SESSION["dbuser"]. "<br>".i18n("Database prefix").": ".$_SESSION["dbprefix"],
						  i18n("config.php").":" => $sConfigMode);
						  
		if ($_SESSION["setuptype"] == "setup")
		{
			$aChoices = array(	"CLIENTEXAMPLES" => i18n("Client with example modules and example content"),
								"CLIENTMODULES"  => i18n("Client with example modules but without example content"),
								"CLIENT"		 => i18n("Client without examples"),
								"NOCLIENT"		 => i18n("Don't create a client"));
			$messages[i18n("Client installation").":"] = $aChoices[$_SESSION["clientmode"]];
		}
		
		
		$cHTMLFoldableErrorMessages = array();
		
		foreach ($messages as $key => $message)
		{
			$cHTMLFoldableErrorMessages[] = new cHTMLInfoMessage($key, $message);
		}
		
		$cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);
		
		$this->_oStepTemplate->set("s", "CONTROL_SETUPSUMMARY", $cHTMLErrorMessageList->render());
		
		$this->setNavigation($previous, $next);
	}
		
}

?>