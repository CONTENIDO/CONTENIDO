<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Module translation editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.3
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-09-22, Murat Purc, Fixed setting of wrong initial translation id [#CON-347]
 *   modified 2011-01-11, Rusmir Jusufovic, load code for translating from files ( function: parseModuleForStringsLoadFromFile)
 *   modified 2111-02-03, Rusmir Jusufovic, load and save the translation in/from file 
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$langobj = new Language;
$langobj->loadByPrimaryKey($lang);

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
    	
}
if ($action == "mod_importexport_translation")
{
	if ($mode == "export")
	{
		$sFileName = uplCreateFriendlyName(strtolower($module->get("name") . "_" . $langobj->get("name")));

		if ($sFileName != "")
		{
			$moduletranslations->export($idmod, $lang,  $sFileName . ".xml",$contenidoTranslateFromFile);
		}
	}
	if ($mode == "import")
	{
		if (file_exists($_FILES["upload"]["tmp_name"]))
		{
		    
			$moduletranslations->import($idmod, $lang, $_FILES["upload"]["tmp_name"],$contenidoTranslateFromFile);
		}
	}
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

$form->add(i18n("Translated Name"), $transmodname);

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


$import = new cHTMLRadiobutton("mode", "import");
$export = new cHTMLRadiobutton("mode", "export");
$export->setLabelText(i18n("Export to file"));
$import->setLabelText(i18n("Import from file"));

$import->setEvent("click", "document.getElementById('vupload').style.display = '';");
$export->setEvent("click", "document.getElementById('vupload').style.display = 'none';");
$upload = new cHTMLUpload("upload");

$import->setChecked("checked");
$form2 = new UI_Table_Form("export");
$form2->setVar("action", "mod_importexport_translation");
$form2->addHeader("Import/Export");
$form2->add(i18n("Mode"), array($export, "<br>", $import));
$form2->add(i18n("File"), $upload, "vupload");
$form2->setVar("area", $area);
$form2->setVar("frame", $frame);
$form2->setVar("idmod", $idmod);
$form2->setVar("idmodtranslation", $idmodtranslation);
$form2->custom["submit"]["accesskey"] = '';

$page->setContent($form->render(). $mark ."<br>". $form2->render());
$page->setMarkScript(2);

$clang = new Language;
$clang->loadByPrimaryKey($lang);

$page->setEncoding($clang->get("encoding"));

if (!($action == "mod_importexport_translation" && $mode == "export"))
{
	$page->render();
}

?>