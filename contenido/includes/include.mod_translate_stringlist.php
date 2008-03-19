<?php
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "contenido/class.module.php");
cInclude("classes", "class.htmlelements.php");


$translations = new cApiModuleTranslationCollection;
$translations->select("idmod = '$idmod' AND idlang='$lang'");

$page = new cPage;
$page->setMargin(0);

//$strings = $module->parseModuleForStrings();

$v = '<table cellspacing="0" cellpadding="0" width="600">';

$link = new cHTMLLink;
$link->setCLink("mod_translate", 4, "");

$mylink = new cHTMLLink;

while ($translation = $translations->next())
{
	$string = $translation->get("original");
	$tstring = $translation->get("translation");
	
    $link->setCustom("idmod", $idmod);
    $link->setCustom("idmodtranslation", $translation->get("idmodtranslation"));
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
	
	if ($idmodtranslation == $translation->get("idmodtranslation"))
	{
		$bgcol = $cfg["color"]["table_active"];
	}
	$v .= '<tr bgcolor="'.$bgcol.'"><td style="padding-left: 2px; padding-top:2px; padding-bottom: 2px;" width="50%"><a name="'.$translation->get("idmodtranslation").'"></a>'.$mylink->render().'</td><td style="padding-left: 2px;">'.$tstring.'</td></tr>';
}

$v .= '</table>';

$page->setContent($v);

$clang = new Language;
$clang->loadByPrimaryKey($lang);

$page->setEncoding($clang->get("encoding"));

$page->render();

?>