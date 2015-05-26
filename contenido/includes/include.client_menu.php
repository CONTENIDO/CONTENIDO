<?php

/**
 * This file contains the menu frame backend page for client management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$clientColl = new cApiClientCollection();

if (!isset($action)) {
    $action = '';
}

if ($action == 'client_delete') {
    if ($perm->have_perm_area_action('client', 'client_delete')) {
        $clientColl->delete((int) $idclientdelete);

        $cfgClient[$idclientdelete] = NULL;

        updateClientCache();
    }
}

$clientColl->select();

while ($oClient = $clientColl->next()) {
    $idclient = $oClient->get('idclient');
    $name = $oClient->get('name');
    if ((strpos($auth->auth['perm'], "admin[$idclient]") !== false) || (strpos($auth->auth['perm'], 'sysadmin') !== false)) {
        $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&idclient=$idclient"), 'right_bottom', $sess->url("main.php?area=client_edit&frame=4&idclient=$idclient"), $name);

        if (!$oClient->hasLanguages() && $perm->have_perm_area_action('client', 'client_delete')) {
            $delTitle = i18n("Delete client");
            $delDescr = sprintf(i18n("Do you really want to delete the following client:<br><br>%s<br>"), conHtmlSpecialChars($name));
            $tpl->set('d', 'DELETE', '<a title="' . $delTitle . '" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteClient(&quot;' . $idclient . '&quot;); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '"></a>');
        } else {
            $tpl->set('d', 'DELETE', '&nbsp;');
        }

        $tpl->set('d', 'ICON', '<img src="images/spacer.gif" alt="" width="12">');
        $tpl->set('d', 'BGCOLOR', $bgColor);
        $tpl->set('d', 'TEXT', $mstr);

        if ($_GET['idclient'] == $idclient) {
            $tpl->set('d', 'ID', 'id="marked"');
        } else {
            $tpl->set('d', 'ID', '');
        }

        $tpl->next();
    }
}

// $sql = "SELECT * FROM " . $cfg["tab"]["clients"];
// $db->query($sql);

// while ($db->nextRecord()) {
//     $idclient = $db->f("idclient");
//     if ((strpos($auth->auth["perm"], "admin[$idclient]") !== false) || (strpos($auth->auth["perm"], "sysadmin") !== false)) {
//         $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
//         $idclient = $db->f("idclient");
//         $mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&idclient=$idclient"), 'right_bottom', $sess->url("main.php?area=client_edit&frame=4&idclient=$idclient"), $db->f("name"));

//         if (!$classclient->hasLanguageAssigned($idclient) && $perm->have_perm_area_action('client', "client_delete")) {
//             $delTitle = i18n("Delete client");
//             $delDescr = sprintf(i18n("Do you really want to delete the following client:<br><br>%s<br>"), conHtmlSpecialChars($db->f("name")));
//             $tpl->set('d', 'DELETE', '<a title="' . $delTitle . '" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteClient(&quot;' . $idclient . '&quot;); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '"></a>');
//         } else {
//             $tpl->set('d', 'DELETE', '&nbsp;');
//         }

//         $tpl->set('d', 'ICON', '<img src="images/spacer.gif" width="12">');
//         $tpl->set('d', 'BGCOLOR', $bgColor);
//         $tpl->set('d', 'TEXT', $mstr);

//         if ($_GET['idclient'] == $idclient) {
//             $tpl->set('d', 'ID', 'id="marked"');
//         } else {
//             $tpl->set('d', 'ID', '');
//         }

//         $tpl->next();
//     }
// }

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_menu']);
