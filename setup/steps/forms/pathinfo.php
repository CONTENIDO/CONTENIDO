<?php

class cSetupPath extends cSetupMask
{
	function cSetupPath ($step, $previous, $next)
	{
		cSetupMask::cSetupMask("templates/setup/forms/pathinfo.tpl", $step);
		$this->setHeader(i18n("System Directories"));
		$this->_oStepTemplate->set("s", "TITLE", i18n("System Directories"));
		$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please check the directories identified by the system. If you need to change a path, click on the name and enter the new path in the available input box."));
		
		list($root_path, $root_http_path) = getSystemDirectories(true);
		
		$cHTMLErrorMessageList = new cHTMLErrorMessageList;
		$cHTMLErrorMessageList->setStyle("width: 580px; height: 200px; overflow: auto; border: 1px solid black;");
		$cHTMLFoldableErrorMessages = array();
		
		list($a_root_path, $a_root_http_path) = getSystemDirectories();
		$oRootPath = new cHTMLTextbox("override_root_path", $a_root_path);
		$oRootPath->setWidth(100);
		$oRootPath->setClass("small");
		$oWebPath = new cHTMLTextbox("override_root_http_path", $a_root_http_path);
		$oWebPath->setWidth(100);
		$oWebPath->setClass("small");
		
		$cHTMLFoldableErrorMessages[0] = new cHTMLFoldableErrorMessage(i18n("Contenido Root Path").":<br>".$root_path, $oRootPath);
		$cHTMLFoldableErrorMessages[0]->_oContent->setStyle("padding-bottom: 8px;");
		$cHTMLFoldableErrorMessages[1] = new cHTMLFoldableErrorMessage(i18n("Contenido Web Path").":<br>".$root_http_path, $oWebPath);
		$cHTMLFoldableErrorMessages[1]->_oContent->setStyle("padding-bottom: 8px;");
		
		$cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);
		
		
		$this->_oStepTemplate->set("s", "CONTROL_PATHINFO", $cHTMLErrorMessageList->render());

		$this->setNavigation($previous, $next);		
	}
}
?>