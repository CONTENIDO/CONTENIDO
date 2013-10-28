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
 *   $Id: setupsummary.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}



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

		$messages = array(
                    i18n("Installation type") . ":" => $sType,
                    i18n("Database parameters") . ":" => i18n("Database host") . ": " . $_SESSION["dbhost"] . "<br>" . 
                                                         i18n("Database name") . ": " . $_SESSION["dbname"] . "<br>" .
                                                         i18n("Database username") . ": " . $_SESSION["dbuser"] . "<br>" .
                                                         i18n("Table prefix") . ": " . $_SESSION["dbprefix"] . "<br>" .
                                                         i18n("Database character set") . ": " . $_SESSION["dbcharset"],
		);

		if ($_SESSION["setuptype"] == "setup")
		{
			$aChoices = array(	"CLIENTEXAMPLES" => i18n("Client with example modules and example content"),
								"CLIENTMODULES"  => i18n("Client with example modules but without example content"),
								"CLIENT"		 => i18n("Client without examples"),
								"NOCLIENT"		 => i18n("Don't create a client"));
			$messages[i18n("Client installation").":"] = $aChoices[$_SESSION["clientmode"]];
		}

        // additional plugins
        $aPlugins = $this->_getSelectedAdditionalPlugins();
        if (count($aPlugins) > 0) {
            $messages[i18n("Additional Plugins").":"] = implode('<br>', $aPlugins);;
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

    function _getSelectedAdditionalPlugins()
    {
        $aPlugins = array();
        if ($_SESSION['plugin_newsletter'] == 'true') {
            $aPlugins[] = i18n("Newsletter");
        }
        if ($_SESSION['plugin_content_allocation'] == 'true') {
            $aPlugins[] = i18n("Content Allocation");
        }
        if ($_SESSION['plugin_mod_rewrite'] == 'true') {
            $aPlugins[] = i18n("Mod Rewrite");
        }
        return $aPlugins;
    }
}

?>