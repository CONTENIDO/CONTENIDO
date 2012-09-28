<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * TINYMCE 1.45rc1 PHP WYSIWYG editor control
 * Main editor file for CONTENIDO
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_notice 
 * TINYMCE 1.45rc1 Fileversion
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    0.0.3
 * @author     Martin Horwath, horwath@dayside.net
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2005-06-10
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id: editor.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}


// include editor config/combat file
include (dirname(__FILE__).DIRECTORY_SEPARATOR."config.php"); // CONTENIDO
cInclude("external", "wysiwyg/tinymce3/editorclass.php");

// name of textarea element
if (isset($type)) {
	$editor_name = "CMS_HTML"; // this should be $type (might be a contenido bug)
} else {
	$editor_name = "content";
}

// if editor is called from any include.CMS_*.html file use available content from $a_content
if ($a_content[$type][$typenr]) {
	$editor_content = $a_content[$type][$typenr];
	// if not set it is possible to use available content from var $editor_content
}

$editor_content = conHtmlSpecialChars($editor_content);

$cTinyMCEEditor = new cTinyMCEEditor($editor_name, $editor_content);

switch ($type)
{
	case "CMS_HTML":
		$editor_height = getEffectiveSetting("wysiwyg", "tinymce-height-html", false);
		
		if ($editor_height == false)
		{
			$editor_height = getEffectiveSetting("tinymce", "contenido_height_html", false);
		}
		break;
	case "CMS_HTMLHEAD":
		$editor_height = getEffectiveSetting("wysiwyg", "tinymce-height-head", false);
		
		if ($editor_height == false)
		{
			$editor_height = getEffectiveSetting("tinymce", "contenido_height_head", false);
		}
		break;
	default:
		$editor_height = false;
}

if ($editor_height !== false)
{
	$cTinyMCEEditor->setSetting("height", $editor_height, true);	
}

/*
TODO:

-> see editor_template.js
-> create own theme template engine
-> maybe change the way icons are displayed
*/

$currentuser = new User;
$currentuser->loadUserByUserID($auth->auth["uid"]);

if ($currentuser->getField("wysi") == 1)
{
	echo $cTinyMCEEditor->getScripts();
	echo $cTinyMCEEditor->getEditor();
} else {
	$oTextarea = new cHTMLTextarea($editor_name, $editor_content);
	$oTextarea->setId($editor_name);
		
	$bgColor 		= getEffectiveSetting("wysiwyg", "tinymce-backgroundcolor", "white");
	$editor_width	= getEffectiveSetting("wysiwyg", "tinymce-width",  "600");
	$editor_height	= getEffectiveSetting("wysiwyg", "tinymce-height", "480");
	
	$oTextarea->setStyle("width: ".$editor_width."px; height: ".$editor_height."px; background-color: ".$bgColor.";");

	echo $oTextarea->render();
}
?>