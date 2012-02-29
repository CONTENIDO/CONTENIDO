<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Module translation editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.3
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-09-22, Murat Purc, Fixed setting of wrong initial translation id [#CON-347]
 *   modified 2011-01-11, Rusmir Jusufovic, load code for translating from files ( function: parseModuleForStringsLoadFromFile)
 *   modified 2111-02-03, Rusmir Jusufovic, load and save the translation in/from file 
 *	 modified 2012-02-12, Rusmir Jusufovic, show message at edit
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$langobj = new cApiLanguage($lang);

$langstring = $langobj->get("name") . ' ('.$lang.')';

$moduletranslations = new cApiModuleTranslationCollection;
$module = new cApiModule($idmod);

$orginalString = "";
$uebersetztungString = "";
$contenidoTranslateFromFile = new Contenido_Translate_From_File($idmod);
if ($action == "mod_translation_save")
{
   
    
    $orginalString = $t_orig;
    $uebersetztungString = $t_trans;
    
    $transaltionArray = $contenidoTranslateFromFile->getTranslationArray();
    
    $transaltionArray[stripslashes($t_orig)] = stripslashes($t_trans);
    //print_r($transaltionArray);
    $contenidoTranslateFromFile->saveTranslationArray($transaltionArray);
   	$notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Saved translation successfully!"));	
    	
}

if (!isset($idmodtranslation))
{
	$idmodtranslation = 0;
}
#get the mi18n strings from modul input/output
$strings = $module->parseModuleForStringsLoadFromFile($cfg , $client,$lang);

#get the strings from translation file
$transaltionArray = $contenidoTranslateFromFile->getTranslationArray();


$myTrans = array();
$save = false;
/* Insert new strings */
foreach ($strings as $string)
{
    if( isset($transaltionArray[$string]))
        $myTrans[$string] = $transaltionArray[$string];
    else {
        $myTrans[$string] = '';	
    	
    }
}

#if changed save in file
if(count(array_diff_assoc($myTrans, $transaltionArray))>0 || count(array_diff_assoc($transaltionArray,$myTrans))>0 )
	$contenidoTranslateFromFile->saveTranslationArray($myTrans);	

if(!isset($row)) {
    $row = count($strings)-1;//last string
    
    $lastString = end($strings);
    $lastUebersetzung = $myTrans[$lastString];

} else {//get the string
    $index = 0;
    foreach( $myTrans as $key =>$value) {

        if($index == $row) {
                $lastString = $key;
                $lastUebersetzung = $value;
                break;
        }
        
        $index++;
    }  
}
$page = new cPage;

$form = new UI_Table_Form("translation");
$form->addHeader(sprintf(i18n("Translate module '%s'"), $module->get("name")));
$form->setVar("area", $area);
$form->setVar("frame", $frame);
$form->setVar("idmod", $idmod);
//$form->setVar("idmodtranslation", $idmodtranslation);
$form->setVar("row", $row);
$form->setVar("action", "mod_translation_save");

$transmodname = new cHTMLTextbox("translatedname", $module->getTranslatedName(),60);

$form->add(i18n("Translated name"), $transmodname);

$ilink = new cHTMLLink;
$ilink->setCLink("mod_translate", 5, "");
$ilink->setCustom("idmod", $idmod);
$ilink->setCustom("row", $row);
//$ilink->setCustom("idmodtranslation", $mtrans->get("idmodtranslation"));
$ilink->setAnchor($row);//$mtrans->get("idmodtranslation"));

$iframe = '<iframe frameborder="0" style="border: 1px;border-color: black; border-style: solid;" width="620" src="'.$ilink->getHREF().'"></iframe>';

$table = '<table border="0" width="600" border="0"><tr><td width="50%">'.i18n("Original module string").'</td><td width="50%">'.sprintf(i18n("Translation for %s"), $langstring).'</td><td width="20">&nbsp;</td></tr><tr><td colspan="3">'.$iframe.'</td></tr>';


$original = new cHTMLTextarea("t_orig",htmlspecialchars($lastString));////$mtrans->get("original")));
$original->setStyle("width: 300px;");
$translated = new cHTMLTextarea("t_trans",htmlspecialchars($lastUebersetzung));//$mtrans->get("translation")));
$translated->setStyle("width: 300px;");

$table .= '<tr><td>'.$original->render().'</td><td>'.$translated->render().'</td><td width="20">&nbsp;</td></tr></table>';
$table .= i18n("Hint: Hit ALT+SHIFT+S to save the translated entry and advance to the next string.");
$form->add(i18n("String list"), $table);

$mark = '<script language="JavaScript">document.translation.t_trans.focus();</script>';


$page->setContent($form->render(). $mark ."<br>");
$page->setMarkScript(2);
$page->setEncoding($langobj->get("encoding"));
$page->render();
?>