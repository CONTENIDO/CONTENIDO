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
    $path1 = $backendUrl . 'main.php?area=con_content_list&action=10&changeview=edit&idart='.$idart.'&idartlang='.$idartlang.
            '&idcat='.$idcat.'&client='.$client.'&lang='.$lang.'&frame=4&contenido='.$contenido;
    $path2 = $path1;
} else {
    $tmp_area = "con_editcontent";
    $path1 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client";
    $path2 = $backendUrl . 'external/backendedit/' . "front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat";
}

if ($doedit == "1") {
    global $cfgClient, $client, $upldir, $uplfile;

    cInclude("includes", "functions.upl.php");

    $rootpath = $cfgClient[$client]["path"]["htmlpath"] . $cfgClient[$client]["upload"];

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
                $db->nextRecord();

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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>CONTENIDO</title>
    <link rel="stylesheet" type="text/css" href="<?php echo cRegistry::getBackendUrl() . $cfg["path"]["styles"] ?>contenido.css">
</head>
<body>

<table width="100%" border=0 cellspacing="0" cellpadding="0">
    <tr>
        <td width="10" rowspan="4"><img src="<?php echo $backendUrl . $cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
        <td width="100%"><img src="<?php echo $backendUrl . $cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
        <td width="10" rowspan="4"><img src="<?php echo $backendUrl . $cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
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

        $header = sprintf(i18n("Edit link for container %s"),$typenr);
        $form->addHeader($header);

        if (is_numeric($a_content["CMS_LINK"][$typenr])) {
            $a_link_intern_value = $a_content["CMS_LINK"][$typenr];
            $a_link_extern_value = "";
        } else {
            $a_link_intern_value = "0";
            $a_link_extern_value = $a_content["CMS_LINK"][$typenr];
        }

        $oTxtEXLink = new cHTMLTextbox("CMS_LINKextern", $a_link_extern_value, 60, 255);
        $form->add(i18n("Link"), $oTxtEXLink->render());

        $form->add(i18n("Description"), "<textarea name=CMS_LINKDESCR rows=3 cols=60>".conHtmlSpecialChars($a_content["CMS_LINKDESCR"][$typenr])."</textarea>");


        $form->addCancel($sess->url($path2));
        echo $form->render();

        echo "  </td></tr>";
        echo "  </table>
              </form>";

        ?>
        </td>
    </tr>
</table>
</body>
</html>