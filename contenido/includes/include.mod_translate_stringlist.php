<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Stringlist for module translation
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
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
 *	 modified 2011-06-09, Rusmir Jusufovic load translations from files
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$contenidoTranslateFromFile = new Contenido_Translate_From_File($idmod);
$translationsArray = $contenidoTranslateFromFile->getTranslationArray();

$translations = new cApiModuleTranslationCollection;
$translations->select("idmod = '$idmod' AND idlang='$lang'");

$page = new cPage;
$page->setMargin(0);

$v = '<table cellspacing="0" cellpadding="0" width="600">';

$link = new cHTMLLink;
$link->setCLink("mod_translate", 4, "");

$mylink = new cHTMLLink;

$rowCount = 0;

foreach($translationsArray as $key => $value)
//while ($translation = $translations->next())
{
	$string = $key;// $translation->get("original");
	$tstring = $value;// $translation->get("translation");
	
    $link->setCustom("idmod", $idmod);
    //$link->setCustom("idmodtranslation", $translation->get("idmodtranslation"));
    $link->setCustom("row", $rowCount);
    
    $href = $link->getHREF();
    
    $mylink->setLink('javascript:parent.location="'.$href.'"');
    $mylink->setContent($string);

	$dark = !$dark;

	if ($dark)
	{
		$bgcol = $cfg["color"]["table_dark"];
	} else {
		$bgcol = $cfg["color"]["table_light"];
	}

	if ($rowCount == $row)// $translation->get("idmodtranslation"))
	{
		$bgcol = $cfg["color"]["table_active"];
	}
	//$v .= '<tr bgcolor="'.$bgcol.'"><td style="padding-left: 2px; padding-top:2px; padding-bottom: 2px;" width="50%"><a name="'.$translation->get("idmodtranslation").'"></a>'.$mylink->render().'</td><td style="padding-left: 2px;">'.$tstring.'</td></tr>';
    
    $v .= '<tr bgcolor="'.$bgcol.'"><td style="padding-left: 2px; padding-top:2px; padding-bottom: 2px;" width="50%"><a name="'.$rowCount.'"></a>'.$mylink->render().'</td><td style="padding-left: 2px;">'.$tstring.'</td></tr>';
    $rowCount++;
}

$v .= '</table>';

$page->setContent($v);

$clang = new Language;
$clang->loadByPrimaryKey($lang);

$page->setEncoding($clang->get("encoding"));

$page->render();

?>