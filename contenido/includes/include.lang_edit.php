<?php
/******************************************
* File      :   include.lang_edit.php
* Project   :   Contenido
* Descr     :   Displays rights
*
* Author    :   Timo A. Hummel
*               Jan Lengowski
*
* Created   :   30.04.2003
* Modified  :   12.05.2003
*
* © four for business AG
*****************************************/

cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.ui.php");

includePlugins("languages");

$clang = new Language;
$clang->loadByPrimaryKey($idlang);


$db2 = new DB_Contenido;

$sReload = '<script language="javascript">
                var left_bottom = parent.parent.frames[\'left\'].frames[\'left_bottom\'];
                if (left_bottom) {
                    var href = left_bottom.location.href;
                    left_bottom.location.href = href+"&idlang="+"'.$idlang.'";
                }
            </script>';

if ($action == "lang_newlanguage" || $action == "lang_deletelanguage")
{
    	$page = new UI_Page;
    	$page->addScript('reload', $sReload);
    	$page->render();	
} else
{
	if ($action == "lang_edit")
	{
		callPluginStore("languages");
		
		$language = new Language;
    	$language->loadByPrimaryKey($idlang);
    	
    	$language->setProperty("dateformat", "full", stripslashes($datetimeformat));
    	$language->setProperty("dateformat", "date", stripslashes($dateformat));
    	$language->setProperty("dateformat", "time", stripslashes($timeformat));
	}
    if(!$perm->have_perm_area_action($area, $action))
    {
    
      $notification->displayNotification("error", i18n("Permission denied"));
      
    } else {
    
    if ( !isset($idlang) && $action != "lang_new")
    {
      $notification->displayNotification("error", "no language id given. Usually, this shouldn't happen, except if you played around with your system. if you didn't play around, please report a bug.");
    
    } else {
    
        if (($action == "lang_edit") && ($perm->have_perm_area_action($area, $action)))
        {
        	langEditLanguage($idlang, $langname, $sencoding, $active, $direction);
            $noti = $notification->returnNotification("info", i18n("Changes saved"))."<br>";
        } 
    
    
        $tpl->reset();
        
        $sql = "SELECT
                    A.idlang AS idlang, A.name AS name, A.active as active, A.encoding as encoding, A.direction as direction,
    				B.idclient AS idclient 
                FROM
                    ".$cfg["tab"]["lang"]." AS A,
    				".$cfg["tab"]["clients_lang"]." AS B
                WHERE
                    A.idlang = '".$idlang."' AND
    				B.idlang = '".$idlang."'";
    
        $db->query($sql);
        $db->next_record();
    
    	$form = new UI_Table_Form("lang_properties");
    	$form->setVar("idlang", $idlang);
    	$form->setVar("targetclient", $db->f("idclient"));
    	$form->setVar("action", "lang_edit");
    	$form->setVar("area", $area);
    	$form->setVar("frame", $frame);
    	
    	$form->addHeader(i18n("Edit language"));
    	
    	$eselect = new cHTMLSelectElement("sencoding");
    	
    	$charsets = array();
    	
    	foreach ($cfg['AvailableCharsets'] as $charset)
    	{
    		$charsets[$charset] = $charset;	
    	}
    	
    	$eselect->autoFill($charsets);
    	$eselect->setDefault($db->f("encoding"));
    	
    	$form->add(i18n("Language name"), formGenerateField ("text", "langname", htmlspecialchars($db->f("name")), 40, 255));
    	$form->add(i18n("Encoding"), $eselect);
    	
    	$directionSelect = new cHTMLSelectElement("direction");
    	$directionSelect->autoFill(array("ltr" => i18n("Left to right"), "rtl" => i18n("Right to left")));
    	$directionSelect->setDefault($db->f("direction"));
    	
    	$form->add(i18n("Text direction"), $directionSelect);
    	
    	$form->add(i18n("Active"), formGenerateCheckbox ("active", "1",$db->f("active")));
        
		displayPlugin("languages", $form);
    
        if ($error)
        {
            echo $error;
        }
    
    	$language = new Language;
    	$language->loadByPrimaryKey($idlang);
    	
    	$fulldateformat = new cHTMLTextbox("datetimeformat", $language->getProperty("dateformat", "full"), 30);
    	$dateformat = new cHTMLTextbox("dateformat", $language->getProperty("dateformat", "date"), 30);
    	$timeformat = new cHTMLTextbox("timeformat", $language->getProperty("dateformat", "time"), 30);
    	
      	$form->add(i18n("Date/Time format"), $fulldateformat->render()); 
      	$form->add(i18n("Date format"), $dateformat->render());
      	$form->add(i18n("Time format"), $timeformat->render());
    
        
    
    	$page = new UI_Page;
    	$page->setContent($noti.$form->render());
    	$page->addScript('reload', $sReload);
    	$page->render();
    }
    } 
}
?>
