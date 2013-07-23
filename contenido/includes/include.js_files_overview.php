<?php
/**
 * This file contains the backend page for javascript files overview.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Willi Man
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude("includes", "functions.file.php");

$tpl->reset();

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $oPage = new cGuiPage("html_tpl_files_overview");
    $oPage->abortRendering();
    $oPage->render();
    return;
}

$path = $cfgClient[$client]["js"]["path"];
$sFileType = "js";

$sSession = $sess->id;

$sArea = 'js';
$sActionDelete = 'js_delete';
$sActionEdit = 'js_edit';

$sScriptTemplate = '
<script type="text/javascript">
    function deleteFile(file) {
        parent.parent.frames["right"].frames["right_bottom"].location.href = "main.php?action='.$sActionDelete.'&delfile="+file+"&area='.$sArea.'&frame=4&contenido='.$sSession.'";

        url  = "main.php?area='.$sArea.'";
        //url += "&action='.$sActionDelete.'";
        url += "&frame=2";
        //url += "&delfile=" + file;
        url += "&contenido='.$sSession.'";
        window.location.href = url;
    }
</script>';

$tpl->set('s', 'JAVASCRIPT', $sScriptTemplate);

if (($handle = opendir($path)) !== false && is_dir($path)) {

    $aFiles = array();

    while (($file = readdir($handle)) !== false) {
        if (substr($file, (strlen($file) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType" AND is_readable($path.$file)) {
            $aFiles[] = $file;
        } elseif (substr($file, (strlen($file) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType" AND !is_readable($path.$file)) {
            $notification->displayNotification("error", $file." ".i18n("is not readable!"));
        }
    }
    closedir($handle);

    // display files
    if (is_array($aFiles)) {
        sort($aFiles);

        foreach ($aFiles as $filename) {
            $file = new cApiFileInformationCollection();
            $fileInfo = $file->getFileInformation($filename, "js");

            $tmp_mstr = '<a class=\"action\" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')" alt="%s">%s</a>';

            $html_filename = sprintf($tmp_mstr, 'right_top',
                           $sess->url("main.php?area=$area&frame=3&file=$filename"),
                           'right_bottom',
                           $sess->url("main.php?area=$area&frame=4&action=$sActionEdit&file=$filename&tmp_file=$filename"),
                           $filename, $filename, conHtmlSpecialChars($filename));

            $tpl->set('d', 'FILENAME', $html_filename);
            $tpl->set("d", "DESCRIPTION", ($fileInfo["description"] == "") ? '' : $fileInfo["description"]);

            $delTitle = i18n("Delete File");
            $delDescr = sprintf(i18n("Do you really want to delete the following file:<br><br>%s<br>"),$filename);

            if ($perm->have_perm_area_action('style', $sActionDelete)) {
                $tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript:void(0)" onclick="showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteFile(&quot;' . $filename . '&quot;); });return false;"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'"></a>');
            } else {
                $tpl->set('d', 'DELETE', '');
            }

            if (stripslashes($_REQUEST['file']) == $filename) {
                $tpl->set('d', 'ID', 'id="marked"');
            } else {
                $tpl->set('d', 'ID', '');
            }

            $tpl->next();
       }
    }
} else {
    if ((int) $client > 0) {
        $notification->displayNotification("error", i18n("Directory is not existing or readable!")."<br>$path");
    }
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['files_overview']);
