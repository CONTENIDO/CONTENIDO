<?php

/**
 * This file contains the menu frame backend page for client management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $tpl;

$perm = cRegistry::getPerm();
$action = cRegistry::getAction();
$auth = cRegistry::getAuth();
$cfg = cRegistry::getConfig();

$clientColl = new cApiClientCollection();

if (!isset($action)) {
    $action = '';
}

$requestIdClient = isset($_GET['idclient']) ? cSecurity::toInteger($_GET['idclient']) : 0;

if ($action == 'client_delete') {
    if ($perm->have_perm_area_action('client', 'client_delete')) {
        $idclientdelete = isset($_GET['idclientdelete']) ? cSecurity::toInteger($_GET['idclientdelete']) : 0;
        if ($idclientdelete) {
            $clientColl->delete($idclientdelete);
            $cfgClient[$idclientdelete] = NULL;
            updateClientCache();
        }
    }
}

$clientColl->select();

while ($oClient = $clientColl->next()) {
    $idclient = $oClient->get('idclient');
    $name = $oClient->get('name');
    if ((cString::findFirstPos($auth->auth['perm'], "client[$idclient]") !== false) || (cString::findFirstPos($auth->auth['perm'], 'sysadmin') !== false)) {
        if ($requestIdClient == $idclient) {
            $tpl->set('d', 'ID', 'id="marked" data-id="' . $idclient . '"');
        } else {
            $tpl->set('d', 'ID', 'data-id="' . $idclient . '"');
        }

        $tpl->set('d', 'ICON', '');

        $showLink = '<a class="show_item" href="javascript:void(0)" data-action="show_client">' . conHtmlSpecialChars($name) . '</a>';
        $tpl->set('d', 'TEXT', $showLink);

        if (!$oClient->hasLanguages() && $perm->have_perm_area_action('client', 'client_delete')) {
            $delTitle = i18n("Delete client");
            $deleteLink = '<a class="con_img_button" href="javascript:void(0)" data-action="delete_client" title="' . $delTitle . '">'
                        . cHTMLImage::img($cfg['path']['images'] . 'delete.gif', $delTitle)
                        . '</a>';
        } else {
            $deleteLink = '&nbsp;';
        }
        $tpl->set('d', 'DELETE', $deleteLink);

        $tpl->next();
    }
}

$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following client:<br><br>%s<br>"));

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_menu']);
