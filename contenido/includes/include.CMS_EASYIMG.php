<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * CMS_EASYIMG-Editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     Ing. Christian Schuller (www.maurer-it.com)
 * @copyright  four for business AG <www.4fb.de>
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2003-12-10
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.CMS_EASYIMG.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if ($doedit == "1") {
	cInclude("includes","functions.upl.php");
	$rootpath = $cfgClient[$client]["path"]["htmlpath"] . $cfgClient[$client]["upload"];
	
	if ($action == "cancel")
	{
		header("location:".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client"));	
	} else {
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
            		
            		$sql = "SELECT idupl FROM ".$cfg["tab"]["upl"]." WHERE dirname='$upldir' AND filename='$friendlyName'";
            		$db->query($sql);
            		$db->next_record();
            		
            		$CMS_LINK = $rootpath . $upldir. $friendlyName;

					conSaveContentEntry($idartlang, "CMS_IMG", $typenr, $db->f("idupl"));
					// Note: Not conMakeArticleIndex as img not relevant for the index
                    conGenerateCodeForArtInAllCategories($idart);            		
    			}
        			
    		}
    	}		

        header("location:".$sess->url($cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit"));
	}
}

?>
<html>
<head>
<title>contenido</title>
<link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"] . $cfg["path"]["styles"] ?>contenido.css">
</HEAD>
<script>
        function disp_preview() {
         if (document.editcontent.CMS_IMG.value) {
			if (document.editcontent.CMS_IMG.value == "0")
			{
	          	preview.document.open();
          		preview.document.writeln('<html><body style="padding:0px; margin:0px;"><table border=0 width=100% height=100%><tr><td align="middle"></td></tr></table></body></html>');
          		preview.document.close();
			} else {
				preview.document.open();
          		preview.document.writeln('<html><body style="padding:0px; margin:0px;"><table border=0 width=100% height=100%><tr><td align="middle"><img src="'+imglnk[document.editcontent.CMS_IMG.value]+'"></td></tr></table></body></html>');
          		preview.document.close();
			}
         }
        }

</script> 
<body onLoad="window.setTimeout('disp_preview()',500);">
<table width="100%"  border=0 cellspacing="0" cellpadding="0" bgcolor="#ffffff">
  <tr>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="100%"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
  </tr>
  <tr>
    <td>

<?php
	cInclude("classes","class.ui.php");
	cInclude("classes","class.htmlelements.php");
	cInclude("classes","class.template.php");
	cInclude("includes","functions.forms.php");
	
    getAvailableContentTypes($idartlang);
    
    $dirheight = 5;
    $dirwidth = 300;
    $filewidth = 300;
    $fileheight = 5;
    $descrwidth = 70;
    $descrheight = 5;
    $previewwidth = 600;
    $previewheight = 400;
    
    $dirheight 		= getEffectiveSetting("cms_img", "directory-height", $dirheight);
    $dirwidth 		= getEffectiveSetting("cms_img", "directory-width", $dirwidth);
    $fileheight		= getEffectiveSetting("cms_img", "file-height", $fileheight);
    $filewidth 		= getEffectiveSetting("cms_img", "file-width", $filewidth);
    $descrheight	= getEffectiveSetting("cms_img", "description-height", $descrheight);
    $descrwidth		= getEffectiveSetting("cms_img", "description-width", $descrwidth);    
    $previewwidth	= getEffectiveSetting("cms_img", "preview-width", $previewwidth);
    $previewheight	= getEffectiveSetting("cms_img", "preview-height", $previewheight);
    
    
    // COLLECT DATA
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

	$header = sprintf(i18n("Edit image for container %s"),$typenr);
	$form->addHeader($header);
	
	
	$upload = new cHTMLUpload("uplfile[]");
	
	
	$form->add(i18n("Upload image"), $upload);
	
	$form->unsetActionButton("submit");
	
	$form->setActionButton("cancel", $cfg["path"]["contenido_fullhtml"]."images/but_cancel.gif", i18n("Discard changes"), "c", "cancel");
	$form->setActionButton("submit", $cfg['path']['contenido_fullhtml']."images/but_ok.gif", i18n("Save changes"), "s");
	echo $form->render();
?>
</td></tr></table>
</body>
</HTML>