<?php
/**
 * Project:
 * CONTENIDO Content Management System Backend
 *
 * Description: This file defines the CodeMirror editor integration class.    
 *
 * @package    CONTENIDO Backend
 * @version    1.0.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @since      file available since CONTENIDO 4.9.0
 *
 * $Id: class.edit_area.php 1563 2011-09-12 09:34:51Z fulai.zhang $
 */


/**
 *
 * Description: Class for handling and displaying CodeMirror
 *
 * @version 1.0.0
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 */
class CodeMirror {
    /**
      * Properties which were used to init CodeMirror
      *
      * @var array
      * @access private
      */
    private $_aProperties = array();
    
    /**
      * HTML-ID of textarea which is replaced by CodeMirror
      *
      * @var string
      * @access private
      */
    private $_sTextareaId  = '';
    
    /**
      * defines if textarea is used or not (by system/client/user property)
      *
      * @var boolean
      * @access private
      */
    private $_bActivated = true;
    
    /**
      * defines if js-script for CodeMirror is included on rendering process
      *
      * @var boolean
      * @access private
      */
    private $_bAddScript = true;
    
    /**
      * The CONTENIDO configuration array
      *
      * @var array
      * @access private
      */
    private $_aCfg = array();
	
	/**
      * Language of CodeMirror
      *
      * @var string
      * @access private
      */
    private $_sLanguage  = '';
	
	/**
      * Syntax of CodeMirror
      *
      * @var string
      * @access private
      */
    private $_sSyntax  = '';
    
    /*################################################################*/
    
    /**
      * Constructor of CodeMirror initializes class variables
      *
      * @param  string $sId - The id of textarea which is replaced by editor
      * @param  string $sSyntax - Name of syntax highlighting which is used (html, css, js, php, ...)
      * @param  string $sLang - lang which is used into editor. Notice NOT CONTENIDO language id
      *                         ex: de, en ... To get it from CONTENIDO language use: 
      *                         substr(strtolower($belang), 0, 2) in backend
      * @param  boolean $bAddScript - defines if CodeMirror script is included or not
      *                               interesting when there is more than only one editor on page
      * @param  array $aCfg - The CONTENIDO configuration array
      * @param  boolean $bEditable - Optional defines if content is editable or not
      *
      * @access public
      */
    public function __construct($sId, $sSyntax, $sLang, $bAddScript, $aCfg, $bEditable = true) {    
        //init class variables
        $this->_aProperties = array();
        $this->_aCfg = (array) $aCfg;
        $this->_bAddScript = (boolean) $bAddScript;
        $this->_sTextareaId = (string) $sId;
        $this->_bActivated = true;
		$this->_sLanguage = (string) $sLang;
		$this->_sSyntax = (string) $sSyntax;

        //make content not editable if not allowed
        if ($bEditable == false) {
            $this->setProperty('readOnly', 'true', true);
        }
        
		$this->setProperty('lineNumbers', 'true', true);
		$this->setProperty('matchBrackets', 'true', true);
		$this->setProperty('indentUnit', 4, true);
		$this->setProperty('indentWithTabs', 'true', true);
		$this->setProperty('enterMode', 'keep', false);
		$this->setProperty('tabMode', 'shift', false);

        //internal function which appends more properties to $this->setProperty wich where defined
        //by user or sysadmin in systemproperties / client settings / user settings ...
        $this->_getSystemProperties();
    }
    
    /**
      * Function gets properties from CONTENIDO for CodeMirror and stores it into
      * $this->setProperty so user is able to overwride standard settings or append
      * other settings. Function also checks if CodeMirror is activated or deactivated
      * by user
      *
      * @access private
      */
    private function _getSystemProperties() {
        //check if editor is disabled or enabled by user/admin
        if (getEffectiveSetting("codemirror", "activated", "true") == "false") {
            $this->_bActivated = false;
        }
        
        $aUserSettings = getEffectiveSettingsByType("codemirror");
        foreach ($aUserSettings as $sKey => $sValue) {
            if ($sKey != 'activated') {
                if ($sValue == "true" || $sValue == "false" || is_numeric($sValue)) {
                    $this->setProperty($sKey, $sValue, true);
                } else {
                    $this->setProperty($sKey, $sValue, false);
                }
            }
        }
    }
    
    /**
      * Function for setting a property for CodeMirror to $this->setProperty
      * existing properties were overwritten
      *
      * @param  string $sName - Name of CodeMirror property
      * @param  string $sValue - Value of CodeMirror property
      * @param  boolean $bIsNumeric - Defines if value is numeric or not
      *                               in case of a numeric value, there is no need to use
      *                               quotes
      *
      * @access public
      */
    public function setProperty($sName, $sValue, $bIsNumeric = false) {
        //datatype check
        $sName = (string) $sName;
        $sValue = (string) $sValue;
        $bIsNumeric = (boolean) $bIsNumeric;
        
        //generate a new array for new property
        $aRecord = array();
        $aRecord['name'] = $sName;
        $aRecord['value'] = $sValue;
        $aRecord['is_numeric'] = $bIsNumeric;
        
        //append it to class variable $this->aProperties
        //when key already exists, overwride it
        $this->_aProperties[$sName] = $aRecord;
    }
	
	private function _getSyntaxScripts() {
		$sPath = $this->_aCfg['path']['contenido_fullhtml'] . '/external/codemirror';
	
		$sJs = '';
		$sJsTemplate = '<script type="text/javascript" src="%s/mode/%s/%s.js"></script>';
		
		$aModes = array();
		
		$sSyntax = $this->_sSyntax;
		if ($sSyntax == 'js' || $sSyntax == 'html' || $sSyntax == 'php') {
			$aModes[] = 'javascript';
		}
		
		if ($sSyntax == 'css' || $sSyntax == 'html' || $sSyntax == 'php') {
			$aModes[] = 'css';
		}
		
		if ($sSyntax == 'html' || $sSyntax == 'php') {
			$aModes[] = 'xml';
		}
		
		if ($sSyntax == 'php') {
			$aModes[] = 'php';
			$aModes[] = 'clike';
		}
		
		if ($sSyntax == 'html') {
			$aModes[] = 'htmlmixed';
		}

		foreach ($aModes as $sMode) {
			$sJs .= sprintf($sJsTemplate, $sPath, $sMode, $sMode) . PHP_EOL;
		}
		
		return $sJs;
	}
	
	private function _getSyntaxName() {
		if ($this->_sSyntax == 'php') {
			return 'application/x-httpd-php';
		}
		
		if ($this->_sSyntax == 'html') {
			return 'text/html';
		}
		
		if ($this->_sSyntax == 'css') {
			return 'text/css';
		}
		
		if ($this->_sSyntax == 'js') {
			return 'text/javascript';
		}
	}
    
    /**
      * Function renders js_script for inclusion into an header of a html file
      *
      * @return string - js_script for CodeMirror
      * @access public
      */
    public function renderScript() {
        //if editor is disabled, there is no need to render this script
        if ($this->_bActivated == false) {
            return '';
        }
        
        //if external js file for editor should be included, do this here
        $sJs = '';
        if ($this->_bAddScript) {
			$sConPath = $this->_aCfg['path']['contenido_fullhtml'];
            $sPath = $sConPath . '/external/codemirror/';
			
            $sJs .= '<script type="text/javascript" src="' . $sPath . 'lib/codemirror.js"></script>'. PHP_EOL;
			$sJs .= '<script type="text/javascript" src="' . $sPath . 'lib/util/foldcode.js"></script>'. PHP_EOL;
			$sJs .= '<script type="text/javascript" src="' . $sPath . 'lib/util/dialog.js"></script>'. PHP_EOL;
			$sJs .= '<script type="text/javascript" src="' . $sPath . 'lib/util/searchcursor.js"></script>'. PHP_EOL;
			$sJs .= '<script type="text/javascript" src="' . $sPath . 'lib/util/search.js"></script>'. PHP_EOL;
			$sJs .= '<script type="text/javascript" src="' . $sPath . 'lib/contenido_integration.js"></script>'. PHP_EOL;
			$sJs .= '<script type="text/javascript" src="' . $sConPath . '/scripts/jquery/jquery.js"></script>'. PHP_EOL;
			$sJs .= $this->_getSyntaxScripts();
            $sJs .= '<link rel="stylesheet" href="' . $sPath . 'lib/codemirror.css" />'. PHP_EOL;
			$sJs .= '<link rel="stylesheet" href="' . $sPath . 'lib/util/dialog.css" />'. PHP_EOL;
        }
        
        //define template for edit_area script
        $sJs .= "<script type=\"text/javascript\">
					function toggleCodeMirrorFullscreen_{ID}() { 
						toggleCodeMirrorFullscreenEditor('{ID}');
                    }  		
					
					properties_{ID} = {
						extraKeys: {\"F11\": toggleCodeMirrorFullscreen_{ID}, \"Esc\": toggleCodeMirrorFullscreen_{ID}}
						{PROPERTIES}
					};

					window.setTimeout('initCodeMirror(\"{ID}\", properties_{ID})', 100);
                </script>";
				
		$this->setProperty('mode', $this->_getSyntaxName(), false);
		$this->setProperty('theme', 'default ' . $this->_sTextareaId, false);
        
        //get all stored properties and convert it in order to insert it into CodeMirror js template
        $sProperties = '';
        foreach ($this->_aProperties as $aProperty) {
            if ($aProperty['is_numeric'] == true) {
                $sProperties .= ', '.$aProperty['name'].':'.$aProperty['value']."\n";
            } else {
                $sProperties .= ', '.$aProperty['name'].': "'.$aProperty['value']."\"\n";
            }
        }
        
        //fill js template
        $sTextareaId = $this->_sTextareaId;
		$sJsResult = str_replace('{ID}', $sTextareaId, $sJs);
		$sJsResult = str_replace('{PROPERTIES}', $sProperties, $sJsResult);

        return $sJsResult;
    }
}
?>