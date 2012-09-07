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
 *   $Id: clientmode.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}



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