<?php
/**
 * $RCSfile$: class.edit_area.php
 *
 * Project:
 * Contenido Content Management System Backend
 *
 * Description: This file defines class edit_area. This class allows to add edit_area to any page.
 *              This class renders a javascript code, which includes edit_area. It is possible to 
 *              configure editarea whith a lot of params. For details see: http://www.cdolivet.net/editarea/
 *              editarea/docs/configuration.html Standard properties where set by this class. It is possible
 *              to set further properties in system or client settings in contenido by using type edit_area
 *              This properties where also imported by this class.
 *              
 *
 * @package    Contenido Backend
 * @version    1.0.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @since      file available since 2008-05-07
 *
 * {@internal
 *    created 2008-05-07
 * }}
 *
 * $Id: class.edit_area.php 739 2008-08-27 10:37:54Z timo.trautmann $
 */


/**
 *
 * Description: Class for handling and displaying edit_area
 *
 * @version 1.0.0
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-05-07
 * }}
 *
 */
class EditArea {
    /**
      * Properties which were used to init edit_area
      *
      * @var array
      * @access private
      */
    var $aProperties;
    
    /**
      * HTML-ID of textarea which is replaced by edit_area
      *
      * @var string
      * @access private
      */
    var $sTextareaId;
    
    /**
      * defines if textarea is used or not (by system/client/user property)
      *
      * @var boolean
      * @access private
      */
    var $bActivated;
    
    /**
      * defines if js-script for edit_area is included on rendering process
      *
      * @var boolean
      * @access private
      */
    var $bAddScript;
    
    /**
      * The contenido configuration array
      *
      * @var array
      * @access private
      */
    var $aCfg;
    
    /*################################################################*/
    
    /**
      * Constructor of EditArea initializes class variables
      *
      * @param  string $sId - The id of textarea which is replaced by editor
      * @param  string $sSyntax - Name of syntax highlighting which is used (html, css, js, php, ...)
      * @param  string $sLang - lang which is used into editor. Notice NOT Contenido language id
      *                         ex: de, en ... To get it from contenido language use: 
      *                         substr(strtolower($belang), 0, 2) in backend
      * @param  boolean $bAddScript - defines if edit_area script is included or not
      *                               interesting when there is more than only one editor on page
      * @param  array $aCfg - The contenido configuration array
      * @param  boolean $bEditable - Optional defines if content is editable or not
      *
      * @access public
      */
    function EditArea($sId, $sSyntax, $sLang, $bAddScript, $aCfg, $bEditable = true) {
        //datatype check
        $sId = (string) $sId;
        $sSyntax = (string) $sSyntax;
        $aCfg = (array) $aCfg;
        $sLang = (string) $sLang;
    
        //init class variables
        $this->aProperties = array();
        $this->aCfg = $aCfg;
        $this->bAddScript = $bAddScript;
        $this->sTextareaId = $sId;
        $this->bActivated = true;
        
        //set standard properties for editor which were static or defined by constructor param
        $this->setProperty('start_highlight', 'true', true);
        $this->setProperty('allow_resize', 'both', false);
        $this->setProperty('allow_toggle', 'false', true);
        $this->setProperty('language', $sLang, false);
        $this->setProperty('save_callback', 'save_callback', false);
        $this->setProperty('syntax', $sSyntax, false);
        $this->setProperty('replace_tab_by_spaces', '4', true);
        $this->setProperty('plugins', 'charmap', false);
        $this->setProperty('toolbar', 'save, search, go_to_line, fullscreen, |, undo, redo, |, select_font,|, change_smooth_selection, highlight, reset_highlight, |, charmap, |, help', false);
        
        //make content not editable if not allowed
        if ($bEditable == false) {
            $this->setProperty('is_editable', 'false', true);
        } else {
            $this->setProperty('is_editable', 'true', true);
        }
        
        //internal function which appends more properties to $this->setProperty wich where defined
        //by user or sysadmin in systemproperties / client settings / user settings ...
        $this->getSystemProperties();
    }
    
    /**
      * Function gets properties from contenido for edit_area and stores it into
      * $this->setProperty so user is able to overwride standard settings or append
      * other settings. Function also checks if edit_area is activated or deactivated
      * by user
      *
      * @access private
      */
    function getSystemProperties() {
        //check if editor is disabled or enabled by user/admin
        if (getEffectiveSetting("edit_area", "activated", "true") == "false") {
            $this->bActivated = false;
        }
        
        $aUserSettings = getEffectiveSettingsByType("edit_area");
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
      * Function for setting a property for edit_area to $this->setProperty
      * existing properties were overwritten
      *
      * @param  string $sName - Name of edit_area property
      * @param  string $sValue - Value of edit_area property
      * @param  boolean $bIsNumeric - Defines if value is numeric or not
      *                               in case of a numeric value, there is no need to use
      *                               quotes
      *
      * @access public
      */
    function setProperty($sName, $sValue, $bIsNumeric) {
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
        $this->aProperties[$sName] = $aRecord;
    }
    
    /**
      * Function renders js_script for inclusion into an header of a html file
      *
      * @return string - js_script for edit_area
      * @access public
      */
    function renderScript() {
        //if editor is disabled, there is no need to render this script
        if ($this->bActivated == false) {
            return '';
        }
        
        //if external js file for editor should be included, do this here
        $sJs = '';
        if ($this->bAddScript) {
            $sPath = $this->aCfg['path']['contenido_fullhtml'];
            $sJs .= '<script type="text/javascript" src="'.$sPath.'external/edit_area/edit_area_compressor.php?plugins"></script>'."\n";
            
            $sJs .= '<script type="text/javascript">
                     function save_callback(id, content) {
                        var oForm = document.getElementById(id).form;
                        for (var i = 0; i < oForm.length; ++i) {
                            var element = oForm.elements[i];
                            if((element.id && editAreaLoader.getValue(element.id)) || (element.id == id)) {
                                element.value = editAreaLoader.getValue(element.id);
                            }
                        }
                        oForm.submit();
                    }
                    </script>';
        }
        
        //define template for edit_area script
        $sJs .= '<script type="text/javascript">
                    function init_editarea_%s() { 
                       var oTextarea = document.getElementById("%s")
                       if (!oTextarea) {
                           window.setTimeout("init_editarea_%s()", 50);
                       } else {
                           var width = oTextarea.offsetWidth;
                           oTextarea.style.width = width+"px";
                           editAreaLoader.init({
                                id: "%s"	
                                %s
                           })
                       }
                    }                    
                    window.setTimeout("init_editarea_%s()", 50);
                </script>';
        
        //get all stored properties and convert it in order to insert it into edit_area js template
        $sProperties = '';
        foreach ($this->aProperties as $aProperty) {
            if ($aProperty['is_numeric'] == true) {
                $sProperties .= ', '.$aProperty['name'].':'.$aProperty['value']."\n";
            } else {
                $sProperties .= ', '.$aProperty['name'].': "'.$aProperty['value']."\"\n";
            }
        }
        
        //fill js template, using sprintf
        $sTextareaId = $this->sTextareaId;
        $sJsResult = sprintf($sJs, $sTextareaId, $sTextareaId, $sTextareaId, $sTextareaId, $sProperties, $sTextareaId);
        
        return $sJsResult;
    }
}
?>