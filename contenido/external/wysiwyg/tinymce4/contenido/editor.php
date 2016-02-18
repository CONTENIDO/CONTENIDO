<?php
/**
 * Main editor file for CONTENIDO
 *
 * @package    Core
 * @subpackage Backend
 * @author     Thomas Stauer
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */


defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// include editor config/combat file
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
// cInclude('external', 'wysiwyg/tinymce4/contenido/editorclass.php');

// name of textarea element
if (isset($type)) {
    // this should be depending on the CMS type (available in $type)
    // possible values are 'CMS_HTML' or 'CMS_HTMLHEAD'
    $editor_name = $type;
} else {
    $editor_name = 'content';
}

// we are in backendedit mode at this point, so set some variables to reflect that
$edit = true;
$contenido = 1;

// if editor is called from any include.CMS_*.html file use available content from $a_content
if ($a_content[$type][$typenr]) {
    $editor_content = $a_content[$type][$typenr];
    // if not set it is possible to use available content from var $editor_content
}

$editor_content = str_replace('src="upload', 'src="'.$cfgClient[$client]['path']['htmlpath'].'upload', $editor_content);

$editor_content = conHtmlSpecialChars($editor_content);

$cTinyMCEEditor = new cTinyMCE4Editor($editor_name, $editor_content);

switch ($type) {
    case 'CMS_HTML':
        $editor_height = getEffectiveSetting('tinymce4', 'contenido_height_html', false);
        break;
    case 'CMS_HTMLHEAD':
        $editor_height = getEffectiveSetting('tinymce4', 'contenido_height_head', false);
        break;
    default:
        $editor_height = false;
}

if (false !== $editor_height) {
    $cTinyMCEEditor->_setSetting('height', $editor_height, true);
}


$currentuser = new cApiUser($auth->auth['uid']);

if ($currentuser->getField('wysi') == 1) {
    echo $cTinyMCEEditor->getScripts();
    echo $cTinyMCEEditor->_getEditor();
} else {
    $oTextarea = new cHTMLTextarea($editor_name, $editor_content);
//    $oTextarea->set('id', $editor_name);
    $oTextarea->setId($editor_name);
    $oTextarea->setClass(htmlentities($type));

    $editor_width  = getEffectiveSetting('tinymce4', 'width',  '600');
    $editor_height = getEffectiveSetting('tinymce4', 'height', '480');

    $oTextarea->setStyle('width: '.$editor_width.'px; height: '.$editor_height.'px;');

    echo $oTextarea->render();
}

?>