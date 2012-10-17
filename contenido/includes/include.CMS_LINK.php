<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Include file for editiing content of type CMS_LINK
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-05-07
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


$backendUrl = cRegistry::getBackendUrl();

if (isset($area) && $area == 'con_content_list') {
    $tmp_area = $area;
    $path1 = $backendUrl.'main.php?area=con_content_list&action=10&changeview=edit&idart='.$idart.'&idartlang='.$idartlang.
            '&idcat='.$idcat.'&client='.$client.'&lang='.$lang.'&frame=4&contenido='.$contenido;
    $path2 = $path1;
} else {
    $tmp_area = "con_editcontent";
    $path1 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client";
    $path2 = $backendUrl . 'external/backendedit/' . "front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat";
}

if ($doedit == "1") {
    global $cfgClient, $client, $upldir, $uplfile;

    cInclude("includes","functions.upl.php");

    $rootpath = cRegistry::getFrontendUrl() . $cfgClient[$client]["upload"];

    $CMS_LINK = $CMS_LINKextern;

    if ($CMS_LINKintern) {
        $CMS_LINK = $CMS_LINKintern;
    }

    if ($selectpdf) {
        $CMS_LINK = $rootpath . $selectpdf;
    }
    if ($selectimg) {
        $CMS_LINK = $rootpath . $selectimg;
    }
    if ($selectzip) {
        $CMS_LINK = $rootpath . $selectzip;
    }
    if ($selectaudio) {
        $CMS_LINK = $rootpath . $selectaudio;
    }
    if ($selectany) {
        $CMS_LINK = $rootpath . $selectany;
    }

    if (count($_FILES) == 1) {
        foreach ($_FILES['uplfile']['name'] as $key => $value) {
            if (cFileHandler::exists($_FILES['uplfile']['tmp_name'][$key])) {
                $friendlyName = uplCreateFriendlyName($_FILES['uplfile']['name'][$key]);
                move_uploaded_file($_FILES['uplfile']['tmp_name'][$key], $cfgClient[$client]['upl']['path'].$upldir.$friendlyName);

                uplSyncDirectory($upldir);

                if ($path == "") {
                    $path = "/";
                }

                $sql = "SELECT idupl FROM ".$cfg["tab"]["upl"]." WHERE dirname='".$db->escape($upldir)."' AND filename='".$db->escape($friendlyName)."'";
                $db->query($sql);
                $db->next_record();

                $CMS_LINK = $rootpath . $upldir. $friendlyName;
            }
        }
    }

    // construct the XML structure
    $newWindow = ($CMS_LINKTARGET == '_blank')? 'true' : 'false';
    // if link is a relative path, prepend the upload path
    if (strpos($CMS_LINK, 'http://') == 0 || strpos($CMS_LINK, 'www.') == 0) {
        $link = $CMS_LINK;
    } else {
        $link = $cfgClient[$this->_client]['upl']['path'] . $CMS_LINK;
    }
    $xml = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<linkeditor><type>external</type><externallink>{$link}</externallink><title>{$CMS_LINKDESCR}</title><newwindow>{$newWindow}</newwindow><idart></idart><filename></filename></linkeditor>
EOT;
    conSaveContentEntry($idartlang, "CMS_LINKEDITOR", $typenr, $xml);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForartInAllCategories($idart);
    header("Location:".$sess->url($path1));
}

header("Content-Type: text/html; charset={$encoding[$lang]}");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>CONTENIDO</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>">
    <link rel="stylesheet" type="text/css" href="<?php $backendUrl . $cfg["path"]["styles"] ?>contenido.css">
</head>
<body>
<table width="100%" border=0 cellspacing="0" cellpadding="0">
    <tr>
        <td width="10" rowspan="4"><img src="<?php echo $backendUrl . $cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
        <td width="100%"><img src="<?php echo $backendUrl . $cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
        <td width="10" rowspan="4"><img src="<?php $backendUrl . $cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    </tr>
    <tr>
        <td>
        <?php

        getAvailableContentTypes($idartlang);

        cInclude("includes", "functions.forms.php");
        global $typenr;

        $form = new cGuiTableForm("editcontent", $backendUrl . $cfg["path"]["includes"]."include.backendedit.php");

        $form->setVar("lang", $lang);
        $form->setVar("typenr", $typenr);
        $form->setVar("idart", $idart);
        $form->setVar("idcat", $idcat);
        $form->setVar("idartlang", $idartlang);
        $form->setVar("contenido", $sess->id);
        $form->setVar("action", 10);
        $form->setVar("doedit", 1);
        $form->setVar("type", $type);
        $form->setVar("changeview", "edit");
        $form->setVar("CMS_LINK", $a_content["CMS_LINK"][$typenr]);

        $header = sprintf(i18n("Edit link for container %s"), $typenr);
        $form->addHeader($header);

        if (is_numeric($a_content["CMS_LINK"][$typenr])) {
            $a_link_intern_value = $a_content["CMS_LINK"][$typenr];
            $a_link_extern_value = "";
        } else {
            $a_link_intern_value = "0";
            $a_link_extern_value = $a_content["CMS_LINK"][$typenr];
        }
        $oTxtEXLink = new cHTMLTextbox("CMS_LINKextern", $a_link_extern_value, 60, 255);
        $form->add(i18n("External link"), $oTxtEXLink->render());

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
                    d.idlang = " . (int) $lang . " AND
                    b.idart  = e.idart AND
                    c.idcat = a.idcat AND
                    c.idclient = " . (int) $client . " AND
                    e.idlang = " . (int) $lang . "
                ORDER BY
                    a.idtree";

        $db->query($sql);

        $intlink .= "<select name=\"cms_linkintern\" size=\"1\" onchange=\"editcontent.CMS_LINK.value=this.value; editcontent.CMS_LINKextern.value='';\">";

        if ($a_link_intern_value != 0) {
            $intlink .= "<option value='0'>-- ".i18n("None")." --</option>";
        } else {
            $intlink .= "<option value='0' selected>-- ".i18n("None")." --</option>";
        }

        while ($db->next_record()) {
            $spaces = "";
            for ($i=0; $i<$db->f("level"); $i++) {
                $spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            $spaces .= "> ";

            $tmp_title = $db->f("title");
            if (strlen($tmp_title) > 32) {
                $tmp_title = substr($tmp_title, 0, 32);
            }

            if ($db->f("idcatart") != $a_link_intern_value) {
                $intlink .= "<option value=\"".$db->f("idcatart")."\">$spaces ".$db->f("name")."---".$tmp_title."</option>";
            } else {
                $intlink .= "<option value=\"".$db->f("idcatart")."\" selected>$spaces ".$db->f("name")."---".$tmp_title."</option>";
            }
        }

        $intlink .= "</select>";

        $form->add(i18n("Internal link"), $intlink);


        $pdflink.= "<select name=\"selectpdf\" size='1'>";
        $pdflink.= "<option value=\"\" selected>".i18n("Please choose")."</option>";

        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".cSecurity::toInteger($client)."' AND filetype IN ('pdf','doc','ppt','xls','rtf','dot') ORDER BY dirname, filename";

        $db->query($sql);

        while ($db->next_record()) {
            //get description from con_upl_meta pro id
            $db2 = cRegistry::getDb();
            $sql = "SELECT DISTINCT(description) FROM ".$cfg['tab']['upl_meta']." WHERE "
                 . "idlang='".$lang."' AND idupl=".$db->f('idupl')." ORDER BY id_uplmeta";
            $db2->query($sql);
            $db2->next_record();
            $pdflink.= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db2->f("description")."]</option>";
        }

        $pdflink.= "</select>";

        $form->add(i18n("Link to a document"), $pdflink);


        $imglink .= "<select name=\"selectimg\" size='1'>";
        $imglink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";

        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".cSecurity::toInteger($client)."' AND filetype IN ('png','gif','tif','jpg','jpeg','psd','pdd','iff','bmp','rle','eps','fpx','pcx','jpe','pct','pic','pxr','tga') ORDER BY dirname, filename";

        $db->query($sql);

        while ($db->next_record()) {
            //get description from con_upl_meta pro id
            $db2 = cRegistry::getDb();
            $sql = "SELECT DISTINCT(description) FROM ".$cfg['tab']['upl_meta']." WHERE "
                 . "idlang='".$lang."' AND idupl=".$db->f('idupl')." ORDER BY id_uplmeta";
            $db2->query($sql);
            $db2->next_record();
            $imglink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db2->f("description")."]</option>";
        }

        $imglink .= "</select>";

        $form->add(i18n("Link to an image"), $imglink);

        $ziplink .= "<select name=\"selectzip\" size='1'>";
        $ziplink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";

        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".$client."' AND filetype IN ('zip','arj','lha','lhx','tar','tgz','rar','gz') ORDER BY dirname, filename";

        $db->query($sql);

        while ($db->next_record()) {
            //get description from con_upl_meta pro id
            $db2 = cRegistry::getDb();
            $sql = "SELECT DISTINCT(description) FROM ".$cfg['tab']['upl_meta']." WHERE "
                 . "idlang='".$lang."' AND idupl=".$db->f('idupl')." ORDER BY id_uplmeta";
            $db2->query($sql);
            $db2->next_record();
            $ziplink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db2->f("description")."]</option>";
        }

        $ziplink .= "</select>";

        $form->add(i18n("Link to an archive"), $ziplink);

        $audiolink .= "<select name=\"selectaudio\" size='1'>";
        $audiolink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";

        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".cSecurity::toInteger($client)."' AND filetype IN ('mp3','mp2','avi','mpg','mpeg','mid','wav','mov','wmv') ORDER BY dirname, filename";

        $db->query($sql);

        while ($db->next_record()) {
            //get description from con_upl_meta pro id
            $db2 = cRegistry::getDb();
            $sql = "SELECT DISTINCT(description) FROM ".$cfg['tab']['upl_meta']." WHERE "
                 . "idlang='".$lang."' AND idupl=".$db->f('idupl')." ORDER BY id_uplmeta";
            $db2->query($sql);
            $db2->next_record();
            $audiolink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db2->f("description")."]</option>";
        }

        $audiolink .= "</select>";

        $form->add(i18n("Link to a media file"), $audiolink);


        $anylink .= "<select name=\"selectany\" size=1>";
        $anylink .= "<option value=\"\" selected>".i18n("Please choose")."</option>";

        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".cSecurity::toInteger($client)."' ORDER BY dirname, filename";

        $db->query($sql);

        while ($db->next_record()) {
            //get description from con_upl_meta pro id
            $db2 = cRegistry::getDb();
            $sql = "SELECT DISTINCT(description) FROM ".$cfg['tab']['upl_meta']." WHERE "
                 . "idlang='".$lang."' AND idupl=".$db->f('idupl')." ORDER BY id_uplmeta";
            $db2->query($sql);
            $db2->next_record();
            $anylink .= "<option value=\"".$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db2->f("description")."]</option>";
        }

        $anylink .= "</select>";

        $form->add(i18n("Link to any file"), $anylink);


        cInclude("includes", "functions.upl.php");

        // Laden der Verzeichnisse und Dateien in separate Arrays
        $olddir = getcwd();
        chdir($cfgClient[$client]['upl']['path'].rawurldecode($path));

        $dirlist = uplDirectoryListRecursive($cfgClient[$client]['upl']['path'].rawurldecode($path));

        chdir($olddir);

        $upldirs = '<select name="upldir">';
        $upldirs .= '<option value="/">&lt;upload&gt;/</option>';

        foreach ($dirlist as $key => $value) {
            $upldirs .= '<option value="'.$value["pathstring"].'">'."&lt;upload&gt;/".$value["pathstring"].'</option>';
        }

        $upldirs .= "</select>";

        $form->add(i18n("Upload file"), $upldirs.'<input name="uplfile[]" type="file">');
        $form->add(i18n("Description"), "<textarea name=CMS_LINKDESCR rows=3 cols=60>".conHtmlSpecialChars($a_content["CMS_LINKDESCR"][$typenr])."</textarea>");

        $linktarget = "  <input class=text_medium type=text name=CMS_LINKTARGET value=\"".$a_content["CMS_LINKTARGET"][$typenr]."\" size=60 onchange=\"setlinktargettosomething();\">";

        $form->add(i18n("Target frame"), $linktarget);

        $newwindow =  "  <input type=checkbox name=checkboxlinktarget value=\"1\" onclick=\"setlinktargettoblank();\" ";
        if ($a_content["CMS_LINKTARGET"][$typenr] == "_blank") {
            $newwindow .= " checked";
        }
        $newwindow .= ">".i18n("Open link in new window")."</input>";
        $newwindow .= "
            <script type=\"text/javascript\">
            <!--
            function setlinktargettosomething() {
                document.editcontent.checkboxlinktarget.value = 1;
                document.editcontent.checkboxlinktarget.checked = false;
            }
            function setlinktargettoblank() {
                if (document.editcontent.checkboxlinktarget.value == 1) {
                    document.editcontent.CMS_LINKTARGET.value = \"_blank\";
                    document.editcontent.checkboxlinktarget.value = 0;
                } else {
                    document.editcontent.CMS_LINKTARGET.value = \"\";
                    document.editcontent.checkboxlinktarget.value = 1;
                }
            }
            //-->
            </script>
        ";

        $form->add(i18n("Open in new window"), $newwindow);

        $form->addCancel($sess->url($path2));
        echo $form->render();

        echo "</form>";

        ?>
        </td>
    </tr>
</table>
</body>
</html>