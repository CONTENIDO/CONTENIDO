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
 *   $Id: include.CMS_LINK.php 775 2008-09-03 16:30:44Z timo.trautmann $:
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

header("Content-Type: text/html; charset={$encoding[$lang]}");
?>


<html>
<head>
<title>contenido</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>">
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
        
		$form->add(i18n("External link"),formGenerateField ("text", "CMS_LINKextern", $a_link_extern_value, 60, 255));
        
        $sql = "SELECT
                    *
                FROM
                    ".$cfg["tab"]["cat_tree"]." AS a,
                    ".$cfg["tab"]["cat_art"]." AS b,
                    ".$cfg["tab"]["cat"]." AS c,
                    ".$cfg["tab"]["cat_lang"]." AS d,
                    ".$cfg["tab"]["art_lang"]." AS e
                WHERE
                    a.idcat = b.idcat AND
                    b.idcat = d.idcat AND
                    d.idlang = '".Contenido_Security::toInteger($lang)."' AND
                    b.idart  = e.idart AND
                    c.idcat = a.idcat AND
                    c.idclient = '".Contenido_Security::toInteger($client)."' AND
					e.idlang = '".Contenido_Security::toInteger($lang)."'
                ORDER BY
                    a.idtree";
        
        
        $db->query($sql);

        $intlink .= "<SELECT name=CMS_LINKintern SIZE=1 onChange=\"editcontent.CMS_LINK.value=this.value; editcontent.CMS_LINKextern.value='';\">";

                if ($a_link_intern_value != 0) {
                    $intlink .= "<option value=0>-- ".i18n("None")." --</option>";
                } else {
                    $intlink .= "<option value=0 selected>-- ".i18n("None")." --</option>";
                }

                while ( $db->next_record() ) {

                        $spaces = "";
                        
                        for ($i=0; $i<$db->f("level"); $i++) {
                            $spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;";
                        }
                        
                        $tmp_title = $db->f("title");

                        if ( strlen($tmp_title) > 32 ) {
                            $tmp_title = substr($tmp_title, 0, 32);
                        }
                        
                        $spaces .= "> ";

                        if ( $db->f("idcatart") != $a_link_intern_value ) {
                                $intlink .= "<option value=\"".$db->f("idcatart")."\">$spaces ".$db->f("name")."---".$tmp_title."</option>";
                        } else {
                                $intlink .= "<option value=\"".$db->f("idcatart")."\" selected>$spaces ".$db->f("name")."---".$tmp_title."</option>";
                        }
                }
                        
                $intlink .= "</SELECT>";
                
        $form->add(i18n("Internal link"),$intlink);
		


        $pdflink.= "<SELECT name=\"selectpdf\" SIZE=1>";
        $pdflink.= "<option value=\"\" selected>".i18n("Please choose")."</option>";
        
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".Contenido_Security::toInteger($client)."' AND filetype IN ('pdf','doc','ppt','xls','rtf','dot') ORDER BY dirname, filename";
        
        $db->query($sql);

        while ($db->next_record()) {
            $pdflink.= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
        }

        $pdflink.= "</SELECT>";

		$form->add(i18n("Link to a document"),$pdflink);
		
		
        $imglink .= "<SELECT name=\"selectimg\" SIZE=1>";
        $imglink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";
        
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".Contenido_Security::toInteger($client)."' AND filetype IN ('png','gif','tif','jpg','jpeg','psd','pdd','iff','bmp','rle','eps','fpx','pcx','jpe','pct','pic','pxr','tga') ORDER BY dirname, filename";
        
        $db->query($sql);

        while ($db->next_record()) {
            $imglink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
        }

        $imglink .= "</SELECT>";
        
        $form->add(i18n("Link to an image"),$imglink);
        
        $ziplink .= "<SELECT name=\"selectzip\" SIZE=1>";
        $ziplink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";
        
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".$client."' AND filetype IN ('zip','arj','lha','lhx','tar','tgz','rar','gz') ORDER BY dirname, filename";
        
        $db->query($sql);

        while ($db->next_record()) {
            $ziplink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
        }

        $ziplink .= "</SELECT>";
        
        $form->add(i18n("Link to an archive"),$ziplink);
        
        $audiolink .= "<SELECT name=\"selectaudio\" SIZE=1>";
        $audiolink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";
        
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".Contenido_Security::toInteger($client)."' AND filetype IN ('mp3','mp2','avi','mpg','mpeg','mid','wav','mov','wmv') ORDER BY dirname, filename";
        
        $db->query($sql);

        while ($db->next_record()) {
            $audiolink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
        }

        $audiolink .= "</SELECT>";
        
        $form->add(i18n("Link to a media file"),$audiolink);
        
                 
        $anylink .= "<SELECT name=\"selectany\" SIZE=1>";
        $anylink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";
        
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".Contenido_Security::toInteger($client)."' ORDER BY dirname, filename";
        
        $db->query($sql);

        while ($db->next_record()) {
            $anylink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
        }

        $anylink .= "</SELECT>";
        
        $form->add(i18n("Link to any file"),$anylink);
        
        
       cInclude("includes","functions.upl.php");

        // Laden der Verzeichnisse und Dateien in separate Arrays
        $olddir = getcwd();
        chdir($cfgClient[$client]['upl']['path'].rawurldecode($path));
        
        $dirlist = uplDirectoryListRecursive($cfgClient[$client]['upl']['path'].rawurldecode($path));


		chdir($olddir);

		$upldirs = '<select name="upldir">';
        $upldirs .= '<option value="/">&lt;upload&gt;/</option>';

        foreach ($dirlist as $key => $value)
        {
                $upldirs .= '<option value="'.$value["pathstring"].'">'."&lt;upload&gt;/".$value["pathstring"].'</option>';
        }
        
        $upldirs .= "</select>";
	
        $form->add(i18n("Upload file"),$upldirs.'<input name="uplfile[]" type="file">');
        $form->add(i18n("Description"),"<TEXTAREA name=CMS_LINKDESCR ROWS=3 COLS=60>".htmlspecialchars($a_content["CMS_LINKDESCR"][$typenr])."</TEXTAREA>");
        
        $linktarget = "  <INPUT class=text_medium type=text name=CMS_LINKTARGET VALUE=\"".$a_content["CMS_LINKTARGET"][$typenr]."\" SIZE=60 onChange=\"setlinktargettosomething();\">";
        
        $form->add(i18n("Target frame"),$linktarget);

              $newwindow =  "  <INPUT TYPE=checkbox name=checkboxlinktarget value=\"1\" onClick=\"setlinktargettoblank();\" ";
              
              if ($a_content["CMS_LINKTARGET"][$typenr]=="_blank") 
              {
              	$newwindow .= " checked";
              }
              	$newwindow .= ">".i18n("Open link in new window")."</INPUT>";
              $newwindow .= "	
                    <!---------JavaScript-------------------->
                           <script language=\"JavaScript\">
                <!--
                function setlinktargettosomething() {
                        document.editcontent.checkboxlinktarget.value = 1;
                        document.editcontent.checkboxlinktarget.checked = false;
                }
                        function setlinktargettoblank() {
                        if (document.editcontent.checkboxlinktarget.value == 1) {
                                document.editcontent.CMS_LINKTARGET.value       = \"_blank\";
                                document.editcontent.checkboxlinktarget.value = 0;
                        } else {
                                document.editcontent.CMS_LINKTARGET.value       = \"\";
                                document.editcontent.checkboxlinktarget.value = 1;
                        }
                }
                //-->
                </SCRIPT>
                ";

		$form->add(i18n("Open in new window"),$newwindow);
		
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