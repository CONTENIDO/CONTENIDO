<?php
/**
 * Plugin Systemtools
 *
 * @file easysync.php
 * @project Contenido
 * 
 * @version	1.0
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @created 29.08.2005
 * @modified 29.08.2005 by Willi Man
 */

cInclude("classes","class.htmlelements.php");
cInclude("classes","class.ui.php");
cInclude("classes","class.template.php");
cInclude("classes","widgets/class.widgets.page.php");
cInclude("includes","functions.str.php");
cInclude("includes","functions.tpl.php");
cInclude("includes","functions.lang.php");
cInclude("includes","functions.general.php");
cInclude("includes","functions.i18n.php");

$languages = getLanguageNamesByClient($client);

###################################################
$from = $lang; # synchronise from current language!
###################################################

$sHTMLNotification = '<div style="width: 390px;">';
$sHTMLNotification .= "<p>".$notification->messageBox("info", i18n("Select destination language and submit form", "systemtools").".<br>".i18n("Synchroniseable categories and articels can then be selected.", "systemtools"), 0)."</p>";
$sHTMLNotification .= '</div>';

$selectbox = new cHTMLSelectElement("to");
$option = new cHTMLOptionElement("--- ".i18n("None")." ---", -1);
$selectbox->addOptionElement(-1, $option);

foreach ($languages as $languageid => $languagename)
{
	if ($lang != $languageid)
	{
		$option = new cHTMLOptionElement($languagename . " (".$languageid.")",$languageid);
		$selectbox->addOptionElement($languageid, $option);
	}
}
    
$selectbox->setDefault($from);

$form = new UI_Table_Form("systemtool", $auth->url());
$form->setWidth('350');
$form->addHeader("<b>".i18n("Synchronise language from", "systemtools")." ".$languages[$lang]." (".$lang.")</b>");
$form->add(i18n("Source language:", "systemtools"), $languages[$lang].' ('.$lang.')');
$selectbox->setDefault($to);
$form->add(i18n("Destination language:", "systemtools"), $selectbox->render());


# form actions #####################
if (isset($_REQUEST['to']) AND is_int((int)$_REQUEST['to']) AND $_REQUEST['to'] > 0)
{
	$sHTMLNotification = '';
	
	if (is_array($synccat))
	{
		foreach ($synccat as $cat)
		{
			strSyncCategory($cat, $from, $to);
			strMakeVisible($cat, $to, true);
		}
		$sHTMLNotification = "<p>".$notification->returnNotification("info", i18n("Categories to language", "systemtools")." ".$languages[$_REQUEST['to']].' ('.$_REQUEST['to'].') '.i18n("synchronised.", "systemtools"))."</p>";
	}else
	{
		$sHTMLNotification = "<p>".$notification->returnNotification("info", i18n("No category to synchronise.", "systemtools"))."</p>";
	}
	
	if (is_array($syncart))
	{
		foreach ($syncart as $art)
		{
			conSyncArticle ($art, $from, $to);
			conMakeOnline($art, $to);
		}
		$sHTMLNotification .= "<p>".$notification->returnNotification("info", i18n("Articles to language", "systemtools")." ".$languages[$_REQUEST['to']].' ('.$_REQUEST['to'].') '.i18n("synchronised.", "systemtools"))."</p>";	
	}else
	{
		$sHTMLNotification .= "<p>".$notification->returnNotification("info", i18n("No article to synchronise.", "systemtools"))."</p>";
	}
}
####################################


$syncart = array();
$synccat = array();
$destcats = array();
$page = new cPage;

if (isset($_REQUEST['to']) AND is_int((int)$_REQUEST['to']) AND $_REQUEST['to'] > 0)
{
	# get destination categories
	$sql = "SELECT cat_lang.name, cat_lang.idcat, cat_tree.level 
			FROM ".$cfg['tab']['cat_lang']." AS cat_lang, ".$cfg['tab']['cat_tree']." AS cat_tree 
			WHERE cat_lang.idcat = cat_tree.idcat AND cat_lang.idlang = ".$to." ORDER BY cat_tree.idtree ASC";
	#print $sql;
	$db->query($sql);
	
	while ($db->next_record())
	{
		$destcats[$db->f("idcat")]["name"] = $db->f("name");
		$destcats[$db->f("idcat")]["level"] = $db->f("level");
	}
	
}

# get source categories
$sql = "SELECT cat_lang.name, cat_lang.idcat, cat_tree.level 
		FROM ".$cfg['tab']['cat_lang']." AS cat_lang, ".$cfg['tab']['cat_tree']." AS cat_tree 
		WHERE cat_lang.idcat = cat_tree.idcat AND cat_lang.idlang = ".$from." ORDER BY cat_tree.idtree ASC";
#print $sql;
$db->query($sql);

$synccats = array();

while ($db->next_record())
{
	$synccats[$db->f("idcat")]["name"] = $db->f("name");	
	$synccats[$db->f("idcat")]["level"] = $db->f("level");
	$synccats[$db->f("idcat")]["tosync"] = false;
		
	if (!array_key_exists($db->f("idcat"), $destcats))
	{
		$synccats[$db->f("idcat")]["tosync"] = true;
	}
}

#print "<pre>synccats "; print_r($synccats); print "</pre>";

$db2 = new DB_Contenido;

$output = '<pre>';

foreach ($synccats as $key => $value)
{
	# for each source category get articles
	$sql = "SELECT title, art_lang.idart, art_lang.idlang 
			FROM ".$cfg['tab']['art_lang']." AS art_lang, ".$cfg['tab']['cat_art']." AS cat_art 
			WHERE cat_art.idart = art_lang.idart AND cat_art.idcat = ".$key." AND art_lang.idlang = ".$from." ";
	#print $sql."<br>";
	$db->query($sql);
	
	$i = new cHTMLCheckbox("synccat[]", $key);
	$i->setLabelText('<img src="images/folder.gif" style="padding-right: 4px;">'.$value["name"]);
	
	if ($value["tosync"] == false)
	{	
		$i->setDisabled(true);
	}
	$i->setStyle("margin-left: ".($value["level"] * 15)."px");
	$output .=  $i->render();
	
	while ($db->next_record())
	{
		$i = new cHTMLCheckbox("syncart[]", $db->f("idart"));
		$i->setLabelText('<img src="images/article.gif" style="padding-right: 4px;">'.$db->f("title"));
	
    	$i->setStyle("margin-left: ".(($value["level"]+1) * 15)."px");
    	
    	if (isset($_REQUEST['to']) AND is_int((int)$_REQUEST['to']) AND $_REQUEST['to'] > 0)
		{
	    	# compare source articles with destination articles 
	    	$sql = "SELECT art_lang.idart 
					FROM ".$cfg['tab']['art_lang']." AS art_lang 
					WHERE art_lang.idart = ".$db->f("idart")." AND art_lang.idlang = ".$to." ";
			#print $sql."<br>";
	    	$db2->query($sql);
	    	
	    	# disable source article if destination article is allready synchronised
	    	if ($db2->next_record())
	    	{
	    		$i->setDisabled(true);
	    	} 
		}
    	$output .=  $i->render();
	}	
}
$output .= '</pre>';

$cacb = new cHTMLCheckbox("checkall", "1");
$cacb->setEvent("click", "i=document.getElementsByName('syncart[]'); for (j=0;j<i.length;j++) { if (i[j].disabled == false) {i[j].checked = true;}} i=document.getElementsByName('synccat[]'); for (j=0;j<i.length;j++) { if (i[j].disabled == false) {i[j].checked = true;}}");
$cacb->setLabelText("Check all");

$form->add(i18n("Tree of language", "systemtools")." ".$languages[$lang]." (".$lang.")", $output . "\r\n".$cacb->toHTML());

$page->setContent($sHTMLNotification . $form->render());
$page->render();

?>