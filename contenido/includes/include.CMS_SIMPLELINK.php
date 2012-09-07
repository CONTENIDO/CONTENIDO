<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Include file for editiing content of type CMS_LINK
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-07
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.CMS_SIMPLELINK.php 775 2008-09-03 16:30:44Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ($doedit == "1") {
	global $cfgClient;
	global $client;
	global $_FILES;
	global $upldir;
	global $uplfile;
	global $HTTP_POST_FILES;

	cInclude("includes","functions.upl.php");
		
	$rootpath = $cfgClient[$client]["path"]["htmlpath"] . $cfgClient[$client]["upload"];
	
	$CMS_LINK = $CMS_LINKextern;

	if ($CMS_LINKintern)
	{
		$CMS_LINK = $CMS_LINKintern;
	}
	
    if($selectpdf){
        $CMS_LINK = $rootpath . $selectpdf;
    }
    if($selectimg){
        $CMS_LINK = $rootpath . $selectimg;
    }
    if($selectzip){
        $CMS_LINK = $rootpath . $selectzip;
    }        
    if($selectaudio){
        $CMS_LINK = $rootpath . $selectaudio;
    }
    if($selectany){
        $CMS_LINK = $rootpath . $selectany;
    }
    
	if (count($_FILES) == 1)
	{
		foreach ($_FILES['uplfile']['name'] as $key => $value)
		{
			if (file_exists($_FILES['uplfile']['tmp_name'][$key]))
			{
    			$friendlyName = uplCreateFriendlyName($_FILES['uplfile']['name'][$key]);
    			move_uploaded_file($_FILES['uplfile']['tmp_name'][$key], $cfgClient[$client]['upl']['path'].$upldir.$friendlyName);
    			
    			uplSyncDirectory($upldir);
    			
        		if ($path == "") { $path = "/"; }
        		
        		$sql = "SELECT idupl FROM ".$cfg["tab"]["upl"]." WHERE dirname='".Contenido_Security::escapeDB($upldir, $db)."' AND filename='".Contenido_Security::escapeDB($friendlyName, $db)."'";
        		$db->query($sql);
        		$db->next_record();
        		
        		$CMS_LINK = $rootpath . $upldir. $friendlyName;
			}
    			
		}
	}
	
	
    conSaveContentEntry ($idartlang, "CMS_LINK", $typenr, $CMS_LINK);
    conSaveContentEntry ($idartlang, "CMS_LINKDESCR", $typenr, $CMS_LINKDESCR);
    conSaveContentEntry ($idartlang, "CMS_LINKTARGET", $typenr, $CMS_LINKTARGET);
    conMakeArticleIndex ($idartlang, $idart);
    conGenerateCodeForartInAllCategories($idart);
    Header("Location:".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client"));
}
?>


<html>
<head>
<title>contenido</title>
<link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["styles"] ?>contenido.css">
</HEAD>
<BODY MARGINHEIGHT=0 MARGINWIDTH=0 LEFTMARGIN=0 TOPMARGIN=0>
<table width="100%"  border=0 cellspacing="0" cellpadding="0">
  <tr>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="100%"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
  </tr>
  <tr>
    <td>
    <?php

		
        getAvailableContentTypes($idartlang);

		cInclude("classes","class.ui.php");
		cInclude("classes","class.template.php");
		cInclude("includes","functions.forms.php");
		global $typenr;
		
		$form = new UI_Table_Form("editcontent", $cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php");
		
		$form->setVar("lang",$lang);
		$form->setVar("typenr",$typenr);
		$form->setVar("idart",$idart);
		$form->setVar("idcat",$idcat);
		$form->setVar("idartlang",$idartlang);
		$form->setVar("contenido",$sess->id);
		$form->setVar("action",10);
		$form->setVar("doedit",1);
		$form->setVar("type",$type);
		$form->setVar("changeview","edit");
		$form->setVar("CMS_LINK", $a_content["CMS_LINK"][$typenr]);
		
		$header = sprintf(i18n("Edit link for container %s"),$typenr);
		$form->addHeader($header);
		
        if (is_numeric($a_content["CMS_LINK"][$typenr])) {
                $a_link_intern_value = $a_content["CMS_LINK"][$typenr];
                $a_link_extern_value = "";
        } else {
                $a_link_intern_value = "0";
                $a_link_extern_value = $a_content["CMS_LINK"][$typenr];
        }
        
		$form->add(i18n("Link"),formGenerateField ("text", "CMS_LINKextern", $a_link_extern_value, 60, 255));
        
      
        $form->add(i18n("Description"),"<TEXTAREA name=CMS_LINKDESCR ROWS=3 COLS=60>".htmlspecialchars($a_content["CMS_LINKDESCR"][$typenr])."</TEXTAREA>");
        
        
		
		$tmp_area = "con_editcontent";
		$form->addCancel($sess->url($cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat"));        
        echo $form->render(); 
                      
        echo "  </TD></TR>";
        

        echo "  </TABLE>
                      </FORM>";




?>
</td></tr></table>
</body>
</HTML>