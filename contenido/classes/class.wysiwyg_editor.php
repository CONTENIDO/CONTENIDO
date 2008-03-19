<?php
/*****************************************
* File      :   $RCSfile: class.wysiwyg_editor.php,v $
* Project   :   Contenido
* Descr     :   Base class for all WYSIWYG editors
*
* Author    :   Timo A. Hummel
*               
* Created   :   27.06.2005
* Modified  :   $Date: 2007/06/24 17:45:58 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.wysiwyg_editor.php,v 1.6 2007/06/24 17:45:58 bjoern.behrens Exp $
******************************************/
cInclude("classes", "class.htmlelements.php");

class cWYSIWYGEditor
{
	var $_sPath;
	var $_sEditor;
	var $_sEditorName;
	var $_sEditorContent;
	var $_aSettings;
	
	function cWYSIWYGEditor ($sEditorName, $sEditorContent)
	{
		global $cfg;
		
		$this->_sPath = $cfg['path']['all_wysiwyg_html'];
		$this->setEditorName($sEditorName);
		$this->setEditorContent($sEditorContent);
	}
	
	function setEditorContent ($sContent)
	{
		$this->_sEditorContent = $sContent;	
	}	
	
	function _setEditor ($sEditor)
	{
		global $cfg;

		if (is_dir($cfg['path']['all_wysiwyg'] . $sEditor))
		{
			if (substr($sEditor, strlen($sEditor)-1,1) != "/")
			{
				$sEditor = $sEditor . "/";
			}
			
			$this->_sEditor = $sEditor;
		}	
	}
	
	function setSetting($sKey, $sValue, $bForceSetting = false)
	{
		if ($bForceSetting)
		{
			$this->_aSettings[$sKey] = $sValue;
		} else if (!array_key_exists($sKey, $this->_aSettings)) {
			$this->_aSettings[$sKey] = $sValue;
		}
	}
	
	function getEditorPath ()
	{
		return ($this->_sPath . $this->_sEditor);
	}
	
	function setEditorName ($sEditorName)
	{
		$this->_sEditorName = $sEditorName;	
	}
	
	function getScripts ()
	{
		cError(__FILE__, __LINE__, "You need to override the method getScripts");	
	}
	
	function getEditor ()
	{
		cError(__FILE__, __LINE__, "You need to override the method getEditor");
	}
		
}
?>