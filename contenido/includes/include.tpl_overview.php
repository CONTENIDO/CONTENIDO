<?php

/**
 * This file contains the menu frame (overview) backend page for template management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cTemplate $tpl
 * @var cDb $db
 * @var array $cfg
 * @var string $area
 */

$oClient = cRegistry::getClient();

// Display critical error if client does not exist
if (!$oClient->isLoaded()) {
    $oPage = new cGuiPage("tpl_overview");
    $oPage->displayCriticalError(i18n('No Client selected'));
    $oPage->render();
    return;
}

$client = cSecurity::toInteger(cRegistry::getClientId());

$requestIdTpl = cSecurity::toInteger($_REQUEST['idtpl'] ?? '0');

$hasCommonTplRights = null;

$sql = "SELECT * FROM `%d` WHERE `idclient` = %d ORDER BY `name`";
$db->query($sql, cRegistry::getDbTableName('tpl'), $client);
$tpl->reset();

while ($db->nextRecord()) {
    if (is_null($hasCommonTplRights)) {
        $hasCommonTplRights = (
            $perm->have_perm_area_action("tpl", "tpl_delete") ||
            $perm->have_perm_area_action("tpl", "tpl_duplicate") ||
            $perm->have_perm_area_action("tpl_edit", "tpl_edit") ||
            $perm->have_perm_area_action("tpl_edit", "tpl_new") ||
            $perm->have_perm_area_action("tpl_visual", "tpl_visedit")
        );
    }

    if ($perm->have_perm_item($area, $db->f("idtpl")) || $hasCommonTplRights) {
        $name = (cString::getStringLength(trim($db->f('name'))) > 0) ? $db->f('name') : i18n("-- New template --");
        $name = conHtmlSpecialChars(stripslashes($name));
        $descr = conHtmlSpecialChars(stripslashes($db->f('description') ?? ''));
        $idtpl = $db->f("idtpl");

        // Create show_action item
        $tmp_mstr = '<a href="javascript://" class="show_item" data-action="show_template" title="%s">%s</a>';

        if ($db->f("defaulttemplate") == 1) {
            $mstr = sprintf($tmp_mstr, $descr, '<b>' . $name . '</b>');
        } else {
            $mstr = sprintf($tmp_mstr, $descr, $name);
        }

        $tpl->set('d', 'DATA_ID', $idtpl);

        if ($perm->have_perm_area_action_item("tpl_edit", "tpl_edit", $idtpl)) {
            $tpl->set('d', 'NAME', $mstr);
        } else {
            $tpl->set('d', 'NAME', $name);
        }

        $tpl->set('d', 'DESCRIPTION', ($descr == '') ? '' : $descr);

        // Check if template is in use
        $inUse = tplIsTemplateInUse($idtpl);

        $inUseString = i18n("Click for more information about usage");

        if (!$inUse && ($perm->have_perm_area_action_item("tpl", "tpl_delete", $idtpl))) {
            $delTitle = i18n("Delete template");
            $deleteLink = '<a href="javascript://" data-action="delete_template" title="' . $delTitle . '">'
                        . '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';
            $inUseLink = '<img class="vAlignMiddle" src="images/spacer.gif" alt="" width="16">';
        } else {
            $delTitle = i18n("Template in use, cannot delete");
            $deleteLink = '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete_inact.gif" title="' . $delTitle . '" alt="' . $delTitle . '">';
            $inUseLink = '<a href="javascript://" data-action="inused_template">'
                       . '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'exclamation.gif" title="' . $inUseString . '" alt="' . $inUseString . '"></a>';
        }

        if ($perm->have_perm_area_action_item("tpl", "tpl_dup", $db->f("idtpl"))) {
            $copyTitle = i18n("Duplicate template");
            $copyLink = '<a href="javascript://" data-action="duplicate_template" title="' . $copyTitle . '">'
                        . '<img class="vAlignMiddle" src="' . $cfg["path"]["images"] . 'but_copy.gif' . '" title="' . $copyTitle . '" alt="' . $copyTitle . '"></a>';
        } else {
            $copyLink = '<img class="vAlignMiddle" src="images/spacer.gif" alt="" width="14" height="1">';
        }

        $tpl->set('d', 'INUSE', '&nbsp;' . $inUseLink);
        $tpl->set('d', 'COPY', '&nbsp;' . $copyLink . '&nbsp;');
        $tpl->set('d', 'DELETE', $deleteLink . '&nbsp;');

        $marked = ($requestIdTpl == $idtpl) ? 'marked' : 'tpl' . $tpl->dyn_cnt;
        $tpl->set('d', 'ID', $marked);

        $tpl->next();
    }
}

// Data for show of used info per ajax
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'AJAX_URL', cRegistry::getBackendUrl() . 'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The template '%s' is used for following categories and articles") . ":");
$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following template:<br><br>%s<br>"));

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tpl_overview']);
