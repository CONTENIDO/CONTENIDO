<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Display files from specified directory
 *
 * @package CONTENIDO Backend Includes
 * @author Olaf Niemann, Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.file.php');
$tpl->reset();

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $oPage = new cGuiPage('style_files_overview');
    $oPage->render();
    return;
}

$path = $cfgClient[$client]['css']['path'];
$sFileType = 'css';

$sSession = $sess->id;

$sArea = 'style';

$sActionEdit = 'style_edit';
$sActionDelete = 'style_delete';

$sScriptTemplate = '
<script type="text/javascript">
    function deleteFile(file) {
        //parent.parent.frames["right"].frames["right_bottom"].location.href = "main.php?area=' . $sArea . '&frame=4&action=' . $sActionDelete . '&delfile="+file+"&contenido=' . $sSession . '";
        url  = "main.php?area=' . $sArea . '";
        url += "&action=' . $sActionDelete . '";
        url += "&frame=2";
        //url += "&idsfi=" + idsfi;
        url += "&contenido=' . $sSession . '";
        //window.location.href = url;
        parent.parent.frames["right"].frames["right_bottom"].location.href = "main.php?area=' . $sArea . '&frame=4&action=' . $sActionDelete . '&delfile="+file+"&contenido=' . $sSession . '";
    }
</script>';

$tpl->set('s', 'JAVASCRIPT', $sScriptTemplate);

if (($handle = opendir($path)) !== false) {
    $aFiles = array();

    while (($file = readdir($handle)) !== false) {
        if (substr($file, (strlen($file) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType" and is_readable($path . $file)) {
            $aFiles[] = $file;
        } elseif (substr($file, (strlen($file) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType" and !is_readable($path . $file)) {
            $notification->displayNotification('error', $file . ' ' . i18n('is not readable!'));
        }
    }
    closedir($handle);

    // display files
    if (is_array($aFiles)) {

        sort($aFiles);

        foreach ($aFiles as $filename) {

            $tmp_mstr = '<a class=\"action\" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')" title="%s" alt="%s">%s</a>';

            $html_filename = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&file=$filename"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&action=$sActionEdit&file=$filename&tmp_file=$filename"), $filename, $filename, htmlspecialchars($filename));

            $tpl->set('d', 'FILENAME', $html_filename);

            $delTitle = i18n('Delete File');
            $delDescr = sprintf(i18n('Do you really want to delete the following file:<br><br>%s<br>'), $filename);

            if ($perm->have_perm_area_action('style', $sActionDelete)) {
                $sql = 'SELECT `idsfi` FROM `' . $cfg['tab']['file_information'] . '` WHERE `idclient`=' . cSecurity::toInteger($client) . " AND `filename`='" . cSecurity::toString($filename) . "'";
                $db->query($sql);
                if ($db->next_record()) {
                    $idsfi = $db->f('idsfi');
                }
                $tpl->set('d', 'DELETE', '<a title="' . $delTitle . '" href="javascript:void(0)" onclick="showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteFile(' . $idsfi . '); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '"></a>');
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
} else if ((int) $client > 0) {
    $notification->displayNotification('error', i18n('Directory is not existing or readable!') . "<br>$path");
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['files_overview']);
