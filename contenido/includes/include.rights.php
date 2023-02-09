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

global $notification, $oTpl, $db, $db2, $aViewRights, $bExclusive;

$sess = cRegistry::getSession();
$area = cRegistry::getArea();
$perm = cRegistry::getPerm();
$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();

$userid = (isset($_REQUEST['userid'])) ? cSecurity::toString($_REQUEST['userid']) : '';
$actionarea = (isset($_REQUEST['actionarea'])) ? cSecurity::toString($_REQUEST['actionarea']) : 'area';
$right_list = (isset($_POST['right_list']) && is_array($_POST['right_list'])) ? $_POST['right_list'] : null;
$rights_perms = (isset($_POST['rights_perms'])) ? cSecurity::toString($_POST['rights_perms']) : '';
$rights_clientslang = (isset($_POST['rights_clientslang']) && is_numeric($_POST['rights_clientslang']))
    ? cSecurity::toInteger($_POST['rights_clientslang']) : 0;
$filter_rights = (isset($_POST['filter_rights'])) ? cSecurity::toString($_POST['filter_rights']) : '';

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

// get list of rights
if (!is_array($right_list)) {
    $right_list = cRights::getRightsList();
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
$oHtmlSelect = new cHTMLSelectElement('rights_clientslang', '', 'rights_clientslang', false, NULL, '', 'vAlignMiddle');

$oClientColl = new cApiClientCollection();
$clientList = $oClientColl->getAccessibleClients();
$firstSel = false;
$firstClientsLang = 0;

$availableClients = [];

foreach ($clientList as $key => $value) {
    $sql = "SELECT * FROM " . $cfg["tab"]["lang"] . " AS A, " . $cfg["tab"]["clients_lang"]
        . " AS B WHERE B.idclient=" . cSecurity::toInteger($key) . " AND A.idlang=B.idlang";
    $db->query($sql);

    while ($db->nextRecord()) {

        $idClientsLang = $db->f('idclientslang');

        if ((cString::findFirstPos($userPerms, "client[$key]") !== false)
            && (cString::findFirstPos($userPerms, "lang[" . $db->f("idlang") . "]") !== false)
            && ($perm->have_perm("lang[" . $db->f("idlang") . "]"))) {
            if (!$firstSel) {
                $firstSel = true;
                $firstClientsLang = $idClientsLang;
            }

            if ($rights_clientslang == $idClientsLang) {
                $availableClients[] = [
                    'idClientsLang' => $idClientsLang,
                    'value_name' => $value['name'],
                    'lang_name' => $db->f('name'),
                    'selected' => 1
                ];

                if (!isset($rights_client)) {
                    $firstClientsLang = $idClientsLang;
                }
            } else {
                $availableClients[] = [
                    'idClientsLang' => $idClientsLang,
                    'value_name' => $value['name'],
                    'lang_name' => $db->f('name'),
                    'selected' => 0
                ];
            }
        }
    }
}

// Generate Select Box or simple the value as text
if (count($availableClients) > 0) {
    foreach ($availableClients as $key => $value) {
        $oHtmlSelectOption = new cHTMLOptionElement(conHtmlSpecialChars($value['value_name']) . ' -> '
            . conHtmlSpecialChars($value['lang_name']), $value['idClientsLang'], $value['selected']);
        $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    }
    $oTpl->set('s', 'INPUT_SELECT_CLIENT', $oHtmlSelect->render());
} else {
    $oTpl->set('s', 'INPUT_SELECT_CLIENT', '');
}

if (empty($rights_clientslang)) {
    $rights_clientslang = $firstClientsLang;
}

$aViewRights = [];
$bExclusive = false;

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
    $oHtmlSelect->setDefault($filter_rights);

    // Set global array which defines rights to display
    $aArticleRights = [
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
    ];
    $aCategoryRights = [
        'con_synccat',
        'con_makecatonline',
        'con_makepublic'
    ];
    $aTemplateRights = [
        'con_changetemplate',
        'con_tplcfg_edit'
    ];

    if (!empty($filter_rights)) {
        switch ($filter_rights) {
            case 'article':
                $aViewRights = $aArticleRights;
                break;
            case 'category':
                $aViewRights = $aCategoryRights;
                break;
            case 'template':
                $aViewRights = $aTemplateRights;
                break;
            case 'other':
                $aViewRights = array_merge($aArticleRights, $aCategoryRights, $aTemplateRights);
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
    if (cString::findFirstPos($userPerms, 'sysadmin') !== false) {
        $oTpl->set('s', 'NOTIFICATION', $notification->returnMessageBox('warning', i18n("The selected user is a system administrator. A system administrator has all rights for all clients for all languages and therefore rights can't be specified in more detail."), 0));
    } elseif (cString::findFirstPos($userPerms, 'admin[') !== false) {
        // Account is only assigned to clients with admin rights
        $oTpl->set('s', 'NOTIFICATION', $notification->returnMessageBox('warning', i18n("The selected user is assigned to clients as admin, only. An admin has all rights for a client and therefore rights can't be specified in more detail."), 0));
    } else {
        $oTpl->set('s', 'NOTIFICATION', $notification->returnMessageBox('error', i18n("Current user doesn't have any rights to any client/language."), 0));
    }
    $oTpl->set('s', 'DISPLAY_FILTER', 'none');
}

if (!$bEndScript) {
    $tmp = ob_get_contents();
    ob_end_clean();
    $oTpl->set('s', 'OB_CONTENT', $tmp);
} else {
    $oTpl->set('s', 'OB_CONTENT', '');
}

if ($bEndScript) {
    $oTpl->set('s', 'NOTIFICATION_SAVE_RIGHTS', '');
    $oTpl->set('s', 'RIGHTS_CONTENT', '');
    $oTpl->set('s', 'JS_SCRIPT_BEFORE', '');
    $oTpl->set('s', 'JS_SCRIPT_AFTER', '');
    $oTpl->set('s', 'RIGHTS_CONTENT', '');
    $oTpl->set('s', 'EXTERNAL_SCRIPTS', '');
    $oTpl->generate('templates/standard/' . $cfg['templates']['rights']);
    die();
}
