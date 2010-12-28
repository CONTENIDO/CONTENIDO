<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * CMS_IMG editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.3.3
 * @author     Ing. Christian Schuller (www.maurer-it.com)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2003-12-10
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-07-07, Dominik Ziegler, fixed language bug
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if ($doedit == "1") {
    conSaveContentEntry ($idartlang, "CMS_IMG", $typenr, $CMS_IMG);
    conSaveContentEntry ($idartlang, "CMS_IMGDESCR", $typenr, $CMS_IMGDESCR);
    conMakeArticleIndex ($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("location:".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client"));
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
    if (!isset($img_dir))
    {
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".$client."' AND idupl = '".$a_content["CMS_IMG"][$typenr]."'";
        $db->query($sql);
        $db->next_record();
        $img_dir = $db->f("dirname");
    }
           
    $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".$client."' AND filetype IN ('jpeg', 'jpg', 'gif', 'png') ORDER BY dirname, filename ASC";
    $db->query($sql);
    
    $ds_name = Array();
    $ds_lvl = array();
    
    while ( $db->next_record() )
    {
    
    	$descr = $db->f("description");
    	
        if ( strlen($descr) > 24 )
        {
            $descr = substr($descr, 0, 24);
            $descr .= "..";
        }
        
        // collect data for dir selection
		$dirname = $db->f("dirname");
        $tmp = explode('/', $dirname);
        
        $mypath = array();
        $mylvl = 0;
        
        foreach ($tmp as $value)
        {
        	if ($value != "")
        	{
            	/* Make sure an entry exists for each path component */
    			$mypath[]= $value;
    			
    			$thispath = implode("/", $mypath)."/";
    			
    			if (!in_array($thispath, $ds_name))
    			{
    				$mylvl++;
    				$ds_lvl[$thispath] = $mylvl;
    				$ds_name[$thispath] = $value;
    				$ds_fullpath[$thispath] = $thispath;
    				 
    			}
        	}        	
        }
        if (!in_array($tmp[count($tmp)-2],$ds_name))
        {
            $ds_lvl[$dirname] = count($tmp)-1;
            $ds_name[$dirname] = $tmp[count($tmp)-2];
            $ds_fullpath[$dirname] = $db->f("dirname"); 
        }
        
        if (strcmp($img_dir,$db->f("dirname"))==0)
        {
            $img_list[] = $db->f("filename");
            $img_id[] = $db->f("idupl");
            $img_descr[] = $descr;
        }
     
    }
    
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
	
	$dirselect = new cHTMLSelectElement("img_dir");
	$dirselect->setEvent("change", "doedit.value=0; submit();");
	$dirselect->setSize($dirheight);
	$dirselect->setStyle("width: {$dirwidth}px;");
	
	foreach ($ds_lvl as $key => $value)
	{
		$text = str_repeat("-",$value*2)."> ".$ds_name[$key];

		$option = new cHTMLOptionElement($text, $ds_fullpath[$key]);
		
        switch ($value)
        {
            case 0:
            case 1: $style="background-color:#C0C0C0;"; break;
            case 2: $style="background-color:#D0D0D0;"; break;
            case 3: $style="background-color:#E0E0E0;"; break;
            default: $style="background-color:#F0F0F0;"; break;
        }

		if (strcmp($img_dir,$ds_fullpath[$key])==0)
        {
        	$option->setSelected("selected");	
        }
        
        $dirselect->addOptionElement($key, $option);
		
	}
	
	$script =  '<script language="JavaScript">';
	$script .= "imglnk = new Array();";
    
    if (is_array($img_list))
    {
    	foreach($img_list as $key => $value)
    	{
        	$script .= 'imglnk["'.$img_id[$key].'"] = "'.$cfgClient[$client]["path"]["htmlpath"].$cfgClient[$client]["upl"]["frontendpath"].$img_dir.$img_list[$key].'";';
    	}
    }   
    $script .= "</script>";

	$fileselect = new cHTMLSelectElement("CMS_IMG");
	$fileselect->setEvent("change", "disp_preview(this.value);");
	$fileselect->setSize($fileheight);
	$fileselect->setStyle("width: {$filewidth}px;");

	$option = new cHTMLOptionElement("-- ".i18n("None")." --", "0");
	
	if ($a_content["CMS_IMG"][$typenr] == 0)
	{
		$option->setSelected("selected");
	}

	$fileselect->addOptionElement(-1,$option);
	
	if (is_array($img_list))
	{
        foreach ($img_list as $key => $value)
        {
        	$description = $img_descr[$key];
        	
        	if ($description != "")
        	{
        		$text = $value . " (". $description .")";	
        	} else {
        		$text = $value;
        	}
    
            switch ($key % 2)
            {
                case 0: $style="background-color:#D0D0D0;"; break;
                case 1: $style="background-color:#E0E0E0;"; break;
            }
            
            $option = new cHTMLOptionElement($text, $img_id[$key]);
            
            if ($a_content["CMS_IMG"][$typenr]==$img_id[$key])
    		{
    			$option->setSelected("selected");
    		}
    		
    		$option->setStyle($style);
    		$fileselect->addOptionElement($key, $option);
        }	
	}
		
	$form->add(i18n("Directory / File"), $dirselect->render().$script.$fileselect->render());
	
	$textarea = new cHTMLTextarea("CMS_IMGDESCR", $a_content["CMS_IMGDESCR"][$typenr], $descrwidth, $descrheight);
	$form->add(i18n("Description"), $textarea->render()); 
	
    $preview = '<iframe src="about:blank" name="preview" style="border: 0px; width:'.$previewwidth.'px; height:'.$previewheight.'px;">';
    $preview .= '</iframe>';

	$form->add(i18n("Preview"), $preview);
	
	$form->render(false);
?>
</td></tr></table>
</body>
</HTML>