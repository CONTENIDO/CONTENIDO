<?php
/**
 * Project: CONTENIDO Content Management System Description: CONTENIDO User
 * Rights
 *
 * @package CONTENIDO Backend Includes
 * @version 1.0.2
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

include_once(cRegistry::getBackendPath() . 'includes/functions.rights.php');

if (!isset($actionarea)) {
    $actionarea = 'area';
}

if (!isset($rights_client)) {
    $rights_client = $client;
    $rights_lang = $lang;
}

if (!is_object($db2)) {
    $db2 = cRegistry::getDb();
}

if (!is_object($oTpl)) {
    $oTpl = new cTemplate();
}
$oTpl->reset();

// Set new right_list (=all possible rights)
if (!is_array($right_list)) {
    // Select all rights, actions an their locations without area login
    $sql = "SELECT A.idarea, A.parent_id, B.location, A.name " . "FROM " . $cfg["tab"]["area"] . " AS A LEFT JOIN " . $cfg["tab"]["nav_sub"] . " AS B ON  A.idarea = B.idarea " . "WHERE A.name!='login' AND A.relevant='1' AND A.online='1' GROUP BY A.name ORDER BY A.idarea";
    $db->query($sql);

    while ($db->nextRecord()) {
        if ($db->f('parent_id') == '0') {
            $right_list[$db->f('name')][$db->f('name')]['perm'] = $db->f('name');
            $right_list[$db->f('name')][$db->f('name')]['location'] = $db->f('location');
        } else {
            $right_list[$db->f('parent_id')][$db->f('name')]['perm'] = $db->f('name');
            $right_list[$db->f('parent_id')][$db->f('name')]['location'] = $db->f('location');
        }

        $sql = "SELECT * FROM " . $cfg["tab"]["actions"] . " WHERE idarea=" . (int) $db->f("idarea") . " AND relevant='1'";
        $db2->query($sql);
        while ($db2->nextRecord()) {
            if ($db->f('parent_id') == '0') {
                $right_list[$db->f('name')][$db->f('name')]['action'][] = $db2->f('name');
            } else {
                $right_list[$db->f('parent_id')][$db->f('name')]['action'][] = $db2->f('name');
            }
        }
    }
}

$oTpl->set('s', 'SESS_ID', $sess->id);
$oTpl->set('s', 'ACTION_URL', $sess->url('main.php'));
$oTpl->set('s', 'TYPE_ID', 'userid');
$oTpl->set('s', 'USER_ID', $userid);
$oTpl->set('s', 'AREA', $area);

$oUser = new cApiUser($userid);
$userPerms = $oUser->getField('perms');

ob_start();

$oTpl->set('s', 'RIGHTS_PERMS', $rights_perms);

// Selectbox for clients
$oHtmlSelect = new cHTMLSelectElement('rights_clientslang', '', 'rights_clientslang', false, NULL, "", "vAlignMiddle");

$oClientColl = new cApiClientCollection();
$clientList = $oClientColl->getAccessibleClients();
$firstSel = false;
$firstClientsLang = 0;

$availableClients = array();

foreach ($clientList as $key => $value) {
    $sql = "SELECT * FROM " . $cfg["tab"]["lang"] . " AS A, " . $cfg["tab"]["clients_lang"] . " AS B WHERE B.idclient=" . (int) $key . " AND A.idlang=B.idlang";
    $db->query($sql);

    while ($db->nextRecord()) {

        $idClientsLang = $db->f('idclientslang');

        if ((strpos($userPerms, "client[$key]") !== false) && (strpos($userPerms, "lang[" . $db->f("idlang") . "]") !== false) && ($perm->have_perm("lang[" . $db->f("idlang") . "]"))) {
            if ($firstSel == false) {
                $firstSel = true;
                $firstClientsLang = $idClientsLang;
            }

            if ($rights_clientslang == $idClientsLang) {

                $availableClients[] = array(
                    'idClientsLang' => $idClientsLang,
                    'value_name' => $value['name'],
                    'lang_name' => $db->f('name'),
                    'selected' => 1
                );

                if (!isset($rights_client)) {
                    $firstClientsLang = $idClientsLang;
                }
            } else {
                $availableClients[] = array(
                    'idClientsLang' => $idClientsLang,
                    'value_name' => $value['name'],
                    'lang_name' => $db->f('name'),
                    'selected' => 0
                );
            }
        }
    }
}

// Generate Select Box or simple the value as text
if (count($availableClients) > 1) {

    foreach ($availableClients as $key => $value) {
        $oHtmlSelectOption = new cHTMLOptionElement($availableClients[$key]['value_name'] . ' -> ' . $availableClients[$key]['lang_name'], $availableClients[$key]['idClientsLang'], $availableClients[$key]['selected']);
        $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    }

    $oTpl->set('s', 'INPUT_SELECT_CLIENT', $oHtmlSelect->render());
} else {
    $string = "<span class='vAlignMiddle'>" . $availableClients[0]['value_name'] . " -> " . $availableClients[0]['lang_name'] . "</span>&nbsp;";
    $oTpl->set('s', 'INPUT_SELECT_CLIENT', $string);
}

if (!isset($rights_clientslang)) {
    $rights_clientslang = $firstClientsLang;
}

if ($area != 'user_content') {
    $oTpl->set('s', 'INPUT_SELECT_RIGHTS', '');
    $oTpl->set('s', 'DISPLAY_RIGHTS', 'none');
} else {
    // Filter for displaying rights
    $oHtmlSelect = new cHTMLSelectElement('filter_rights', '', 'filter_rights');
    $oHtmlSelectOption = new cHTMLOptionElement('--- ' . i18n('All') . ' ---', '', false);
    $oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Article rights'), 'article', false);
    $oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Category rights'), 'category', false);
    $oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Template rights'), 'template', false);
    $oHtmlSelect->addOptionElement(3, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Plugin/Other rights'), 'other', false);
    $oHtmlSelect->addOptionElement(4, $oHtmlSelectOption);
    $oHtmlSelect->setEvent('change', 'document.rightsform.submit();');
    $oHtmlSelect->setDefault($_POST['filter_rights']);

    // Set global array which defines rights to display
    $aArticleRights = array(
        'con_syncarticle',
        'con_lock',
        'con_deleteart',
        'con_makeonline',
        'con_makestart',
        'con_duplicate',
        'con_editart',
        'con_newart',
        'con_edit',
        'con_meta_edit',
        'con_meta_deletetype'
    );
    $aCategoryRights = array(
        'con_synccat',
        'con_makecatonline',
        'con_makepublic'
    );
    $aTempalteRights = array(
        'con_changetemplate',
        'con_tplcfg_edit'
    );

    $aViewRights = array();
    $bExclusive = false;
    if (isset($_POST['filter_rights'])) {
        switch ($_POST['filter_rights']) {
            case 'article':
                $aViewRights = $aArticleRights;
                break;
            case 'category':
                $aViewRights = $aCategoryRights;
                break;
            case 'template':
                $aViewRights = $aTempalteRights;
                break;
            case 'other':
                $aViewRights = array_merge($aArticleRights, $aCategoryRights, $aTempalteRights);
                $bExclusive = true;
                break;
            default:
                break;
        }
    }
    $oTpl->set('s', 'INPUT_SELECT_RIGHTS', $oHtmlSelect->render());
    $oTpl->set('s', 'DISPLAY_RIGHTS', 'block');
}

$bEndScript = false;

$oClientLang = new cApiClientLanguage((int) $rights_clientslang);
if ($oClientLang->isLoaded()) {
    $rights_client = $oClientLang->get('idclient');
    $rights_lang = $oClientLang->get('idlang');
    $oTpl->set('s', 'NOTIFICATION', '');
    $oTpl->set('s', 'DISPLAY_FILTER', 'block');
} else {
    $bEndScript = true;
    ob_end_clean();

    // Account is sysadmin
    if (strpos($userPerms, 'sysadmin') !== false) {
        $oTpl->set('s', 'NOTIFICATION', $notification->returnMessageBox('warning', i18n("The selected user is a system administrator. A system administrator has all rights for all clients for all languages and therefore rights can't be specified in more detail."), 0));
    } else if (strpos($userPerms, 'admin[') !== false) {
        // Account is only assigned to clients with admin rights
        $oTpl->set('s', 'NOTIFICATION', $notification->returnMessageBox('warning', i18n("The selected user is assigned to clients as admin, only. An admin has all rights for a client and therefore rights can't be specified in more detail."), 0));
    } else {
        $oTpl->set('s', 'NOTIFICATION', $notification->returnMessageBox('error', i18n("Current user doesn't have any rights to any client/language."), 0));
    }
    $oTpl->set('s', 'DISPLAY_FILTER', 'none');
}

if ($bEndScript != true) {
    $tmp = ob_get_contents();
    ob_end_clean();
    $oTpl->set('s', 'OB_CONTENT', $tmp);
} else {
    $oTpl->set('s', 'OB_CONTENT', '');
}

if ($bEndScript == true) {
    $oTpl->set('s', 'RIGHTS_CONTENT', '');
    $oTpl->set('s', 'JS_SCRIPT_BEFORE', '');
    $oTpl->set('s', 'JS_SCRIPT_AFTER', '');
    $oTpl->set('s', 'RIGHTS_CONTENT', '');
    $oTpl->set('s', 'EXTERNAL_SCRIPTS', '');
    $oTpl->generate('templates/standard/' . $cfg['templates']['rights']);
    die();
}
