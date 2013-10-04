<?php
/**
 * This file contains the menu frame (overview) backend page for template management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$sql = "SELECT * FROM " . $cfg["tab"]["tpl"] . " WHERE idclient = '" . cSecurity::toInteger($client) . "'
        ORDER BY name";

$db->query($sql);
$tpl->reset();

$tpl->set('s', 'SID', $sess->id);

while ($db->nextRecord()) {

    if ($perm->have_perm_item($area, $db->f("idtpl")) ||
            $perm->have_perm_area_action("tpl", "tpl_delete") ||
            $perm->have_perm_area_action("tpl", "tpl_duplicate") ||
            $perm->have_perm_area_action("tpl_edit", "tpl_edit") ||
            $perm->have_perm_area_action("tpl_edit", "tpl_new") ||
            $perm->have_perm_area_action("tpl_visual", "tpl_visedit")
    ) {
        $name = (strlen(trim($db->f('name'))) > 0) ? $db->f('name') : i18n("-- New template --");
        $descr = $db->f('description');
        $idtpl = $db->f("idtpl");

        // create javascript multilink
        $tmp_mstr = '<a title="%s" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

        if ($db->f("defaulttemplate") == 1) {
            $mstr = sprintf(
                    $tmp_mstr, conHtmlSpecialChars($descr), 'right_top', $sess->url("main.php?area=tpl&frame=3&idtpl=$idtpl"), 'right_bottom', $sess->url("main.php?area=tpl_edit&frame=4&idtpl=$idtpl"), "<b>" . $name . "</b>"
            );
        } else {
            $mstr = sprintf(
                    $tmp_mstr, conHtmlSpecialChars($descr), 'right_top', $sess->url("main.php?area=tpl&frame=3&idtpl=$idtpl"), 'right_bottom', $sess->url("main.php?area=tpl_edit&frame=4&idtpl=$idtpl"), $name
            );
        }

        if ($perm->have_perm_area_action_item("tpl_edit", "tpl_edit", $db->f("idtpl"))) {
            $tpl->set('d', 'NAME', $mstr);
        } else {
            $tpl->set('d', 'NAME', $name);
        }

        $tpl->set("d", "DESCRIPTION", ($descr == "") ? '' : $descr);

        // Check if template is in use
        $inUse = tplIsTemplateInUse($idtpl);

        $inUseString = i18n("Click for more information about usage");

        if (!$inUse && ($perm->have_perm_area_action_item("tpl", "tpl_delete", $db->f("idtpl")))) {
            $delTitle = i18n("Delete template");
            $delDescr = sprintf(
                    i18n("Do you really want to delete the following template:<br><br>%s<br>"), conHtmlSpecialChars($name)
            );

            $tpl->set('d', 'DELETE', '
                <a
                    title="' . $delTitle . '"
                    href="javascript:void(0)"
                    onclick="showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteTemplate(' . $idtpl . '); });return false;"
                >
                    <img
                        src="' . $cfg['path']['images'] . 'delete.gif"
                        border="0"
                        title="' . $delTitle . '"
                        alt="' . $delTitle . '" />
                </a>');
            $tpl->set('d', 'INUSE', '<img src="images/spacer.gif" width="16">');
        } else {
            $delDescription = i18n("Template in use, cannot delete");
            $tpl->set('d', 'DELETE', '<img
                    src="' . $cfg['path']['images'] . 'delete_inact.gif"
                    border="0" title="' . $delDescription . '"
                    alt="' . $delDescription . '" />'
            );
            $tpl->set('d', 'INUSE', '
                <a
                    href="javascript:;"
                    rel="' . (int) $db->f("idtpl") . '"
                    class="in_used_tpl"
                >
                    <img
                        src="' . $cfg['path']['images'] . 'exclamation.gif"
                        border="0"
                        title="' . $inUseString . '"
                        alt="' . $inUseString . '" />
                </a>');
        }

        if ($perm->have_perm_area_action_item("tpl", "tpl_dup", $db->f("idtpl"))) {
            $copybutton = '
                <a
                    target="right_bottom"
                    href="' . $sess->url("main.php?area=tpl_edit&action=tpl_duplicate&idtpl=$idtpl&frame=4") . '"
                    title="' . i18n("Duplicate template") . '"
                >
                    <img
                        src="' . $cfg["path"]["images"] . 'but_copy.gif' . '"
                        border="0"
                        title="' . i18n("Duplicate template") . '"
                        alt="' . i18n("Duplicate template") . '" />
                </a>';
        } else {
            $copybutton = '<img src="images/spacer.gif" width="14" height="1" />';
        }

        $tpl->set('d', 'COPY', $copybutton);
        $tpl->set('d', 'ID', 'tpl' . $tpl->dyn_cnt);

        $tpl->next();
    }
}

// datas for show of used info per ajax
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'SESSION', $contenido);
$tpl->set('s', 'AJAXURL', cRegistry::getBackendUrl() . 'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The template '%s' is used for following categories and articles") . ":");
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tpl_overview']);
