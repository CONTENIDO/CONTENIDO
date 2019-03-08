<?php

/**
 * This file contains the backend page for group rights management.
 *
 * @package Core
 * @subpackage Backend
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.rights.php');

global $dataSync;
$dataSync = array();

if (!isset($actionarea)) {
    $actionarea = 'area';
}

if (!is_object($db2)) {
    $db2 = cRegistry::getDb();
}

if (!is_object($oTpl)) {
    // $oTpl = new cTemplate();
}
// $oTpl->reset();

// build list of rights for all relevant and online areas except "login" and their relevant actions
if (!is_array($right_list)) {
    $areaCollection   = new cApiAreaCollection();
    $navSubCollection = new cApiNavSubCollection();
    $actionCollection = new cApiActionCollection();
    try {
        $areaCollection->select('relevant = 1 AND online = 1 AND name != "login"');
        while ($areaItem = $areaCollection->next()) {
            $right = [
                'perm'     => $areaItem->get('name'),
                'location' => '',
            ];
            // get location
            $navSubCollection->select('idarea = ' . (int)$areaItem->get('idarea'));
            if ($navSubItem = $navSubCollection->next()) {
                $right['location'] = $navSubItem->get('location');
            }
            // get relevant actions
            $actions = $actionCollection->select('relevant = 1 AND idarea = ' . (int)$areaItem->get('idarea'));
            while ($actionItem = $actionCollection->next()) {
                $right['action'][] = $actionItem->get('name');
            }
            // insert into list
            if ($areaItem->get('parent_id') == '0') {
                $key = $areaItem->get('name');
            } else {
                $key = $areaItem->get('parent_id');
            }
            $right_list[$key][$areaItem->get('name')] = $right;
        }
    } catch (cDbException $e) {
        $right_list = [];
    } catch (cException $e) {
        $right_list = [];
    }
}

$dataSync['SESS_ID'] = $sess->id;
$dataSync['ACTION_URL'] = array(
    '',
    $sess->url('main.php')
);
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
                $oHtmlSelectOption = new cHTMLOptionElement($value['name'] . ' -> ' . $db->f('name'), $db->f('idclientslang'), true);
                $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
                if (!isset($rights_client)) {
                    $firstClientsLang = $db->f('idclientslang');
                }
            } else {
                $oHtmlSelectOption = new cHTMLOptionElement($value['name'] . ' -> ' . $db->f('name'), $db->f('idclientslang'), false);
                $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
            }
        }
    }
}

if (!isset($rights_clientslang)) {
    $rights_clientslang = $firstClientsLang;
}

// Render Select Box
$dataSync['INPUT_SELECT_CLIENT'] = $oHtmlSelect->render();
// $oTpl->set('s', 'INPUT_SELECT_CLIENT', $oHtmlSelect->render());

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
    $aTemplateRights = array(
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
    $dataSync['DISPLAY_RIGHTS'] = 'block';
    // $oTpl->set('s', 'INPUT_SELECT_RIGHTS', $oHtmlSelect->render());
    // $oTpl->set('s', 'DISPLAY_RIGHTS', 'block');
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
