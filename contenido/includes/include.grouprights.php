<?php

/**
 * This file contains the backend page for group rights management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $db2, $dataSync;

$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();

$groupid = cSecurity::toString($_REQUEST['groupid'] ?? '');
$actionarea = cSecurity::toString($_REQUEST['actionarea'] ?? 'area');
$right_list = $_POST['right_list'] ?? null;
if (!is_array($right_list)) {
    $right_list = null;
}
$rights_perms = cSecurity::toString($_POST['rights_perms'] ?? '');
$rights_clientslang = cSecurity::toInteger($_POST['rights_clientslang'] ?? '0');
$filter_rights = cSecurity::toString($_POST['filter_rights'] ?? '');

$dataSync = [];

if (!is_object($db2)) {
    $db2 = cRegistry::getDb();
}

// get list of rights
if (!is_array($right_list)) {
    $right_list = cRights::getRightsList();
}

$dataSync['SESS_ID'] = $sess->id;
$dataSync['ACTION_URL'] = [
    '',
    $sess->url('main.php')
];
$dataSync['TYPE_ID'] = 'groupid';
$dataSync['USER_ID'] = $groupid;
$dataSync['AREA'] = $area;

// $oTpl->set('s', 'SESS_ID', $sess->id);
// $oTpl->set('s', 'ACTION_URL', $sess->url('main.php'));
// $oTpl->set('s', 'TYPE_ID', 'groupid');
// $oTpl->set('s', 'USER_ID', $groupid);
// $oTpl->set('s', 'AREA', $area);

$oGroup = new cApiGroup($groupid);
$userPerms = $oGroup->getField('perms');

$dataSync['RIGHTS_PERMS'] = $rights_perms;
// $oTpl->set('s', 'RIGHTS_PERMS', $rights_perms);

// Selectbox for clients
$oHtmlSelect = new cHTMLSelectElement('rights_clientslang', '', 'rights_clientslang');

$oClientColl = new cApiClientCollection();
$clientList = $oClientColl->getAccessibleClients();
$firstSel = false;
$firstClientsLang = 0;

foreach ($clientList as $key => $value) {
    $sql = "SELECT * FROM " . $cfg["tab"]["lang"] . " AS A, " . $cfg["tab"]["clients_lang"] . " AS B WHERE B.idclient=" . (int) $key . " AND A.idlang=B.idlang";
    $db->query($sql);

    while ($db->nextRecord()) {
        if ((cString::findFirstPos($userPerms, "client[$key]") !== false) && (cString::findFirstPos($userPerms, "lang[" . $db->f("idlang") . "]") !== false) && ($perm->have_perm("lang[" . $db->f("idlang") . "]"))) {
            if ($firstSel == false) {
                $firstSel = true;
                $firstClientsLang = $db->f('idclientslang');
            }

            if ($rights_clientslang == $db->f('idclientslang')) {
                $oHtmlSelectOption = new cHTMLOptionElement(conHtmlSpecialChars($value['name']) . ' -> ' . conHtmlSpecialChars($db->f('name')), $db->f('idclientslang'), true);
                $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
                if (!isset($rights_client)) {
                    $firstClientsLang = $db->f('idclientslang');
                }
            } else {
                $oHtmlSelectOption = new cHTMLOptionElement(conHtmlSpecialChars($value['name']) . ' -> ' . conHtmlSpecialChars($db->f('name')), $db->f('idclientslang'), false);
                $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
            }
        }
    }
}

if (empty($rights_clientslang)) {
    $rights_clientslang = $firstClientsLang;
}

// Render Select Box
$dataSync['INPUT_SELECT_CLIENT'] = $oHtmlSelect->render();

if ($area != 'groups_content') {
    $dataSync['INPUT_SELECT_RIGHTS'] = '';
    $dataSync['DISPLAY_RIGHTS'] = 'none';

    // $oTpl->set('s', 'INPUT_SELECT_RIGHTS', '');
    // $oTpl->set('s', 'DISPLAY_RIGHTS', 'none');
} else {
    // Filter for displaying rights
    $oHtmlSelect = new cHTMLSelectElement('filter_rights', '', 'filter_rights');
    $oHtmlSelectOption = new cHTMLOptionElement('--- ' . i18n('All') . ' ---', '', false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Article rights'), 'article', false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Category rights'), 'category', false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Template rights'), 'template', false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Plugin/Other rights'), 'other', false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
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

    $aViewRights = [];
    $bExclusive = false;
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

    $dataSync['INPUT_SELECT_RIGHTS'] = $oHtmlSelect->render();
    $dataSync['DISPLAY_RIGHTS'] = 'inline-block';
    // $oTpl->set('s', 'INPUT_SELECT_RIGHTS', $oHtmlSelect->render());
    // $oTpl->set('s', 'DISPLAY_RIGHTS', 'inline-block');
}

$oClientLang = new cApiClientLanguage((int) $rights_clientslang);
if ($oClientLang->isLoaded()) {
    $rights_client = $oClientLang->get('idclient');
    $rights_lang = $oClientLang->get('idlang');
} else {
    $page = new cGuiPage('generic_page');
    $page->displayError(i18n("Current group doesn't have any rights to any client/language."));
    $page->abortRendering();
    $page->render();
    die();
}

// current set it on NULL

$dataSync['NOTIFICATION'] = '';
$dataSync['OB_CONTENT'] = '';
//$oTpl->set('s', 'NOTIFICATION', '');
//$oTpl->set('s', 'OB_CONTENT', '');
