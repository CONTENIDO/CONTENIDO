<?php

class cSetupInstaller extends cSetupMask
{
	function cSetupInstaller ($step)
	{
		cSetupMask::cSetupMask("templates/setup/forms/installer.tpl", $step);
		$this->setHeader(i18n("System Installation"));
		$this->_oStepTemplate->set("s", "TITLE", i18n("System Installation"));
		$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Contenido will be installed, please wait ..."));
		
		$this->_oStepTemplate->set("s", "DBUPDATESCRIPT", "dbupdate.php");
		
		switch ($_SESSION["setuptype"])
		{
			case "setup":
				$this->_oStepTemplate->set("s", "DONEINSTALLATION", i18n("Setup completed installing. Click on next to continue."));
				$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Setup is installing, please wait..."));			
				$_SESSION["upgrade_nextstep"] = "setup7";
				$this->setNavigation("", "setup7");
				break;
			case "upgrade":
				$this->_oStepTemplate->set("s", "DONEINSTALLATION", i18n("Setup completed upgrading. Click on next to continue."));
				$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Setup is upgrading, please wait..."));			
				$_SESSION["upgrade_nextstep"] = "ugprade6";
				$this->setNavigation("", "upgrade6");
				break;
			case "migration":
				$this->_oStepTemplate->set("s", "DONEINSTALLATION", i18n("Setup completed migration. Click on next to continue."));
				$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Setup is migrating, please wait..."));			
				$_SESSION["upgrade_nextstep"] = "migration7";
				$this->setNavigation("", "migration7");
				break;
		}

	}
}
?>