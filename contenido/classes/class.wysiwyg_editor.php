<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Base class for all WYSIWYG editors
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.6
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2005-06-27
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.wysiwyg_editor.php 769 2008-09-03 10:27:23Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
	
    function unsetSetting($sKey) {
        unset($this->_aSettings[$sKey]);
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