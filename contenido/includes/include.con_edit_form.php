<?php

/**
 * This file contains the backend page for displaying and editing article
 * properties.
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

cInclude("includes", "functions.tpl.php");
cInclude("includes", "functions.str.php");
cInclude("includes", "functions.pathresolver.php");

// ugly globals that are used in this script
global $tpl, $db, $selectedArticleId, $contenido, $notification, $lngAct, $idcatart, $idtpl;
global $tplinputchanged, $idcatnew, $newart, $syncoptions, $tmp_notification, $bNoArticle, $artLangVersion, $classarea;

$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$area = cRegistry::getArea();
$action = cRegistry::getAction();
$client = cRegistry::getClientId();
$cfg = cRegistry::getConfig();
$frame = cRegistry::getFrame();
$auth = cRegistry::getAuth();
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$belang = cRegistry::getBackendLanguage();
$idart = cRegistry::getArticleId();
$idcat = cRegistry::getCategoryId();
$idcatlang = cRegistry::getCategoryLanguageId();
$idartlang = cSecurity::toInteger(cRegistry::getArticleLanguageId());

$page = new cGuiPage("con_edit_form", "", "con_editart");
$page->addStyle('version_selection.css');
$tpl = null;

// Admin rights
$isAdmin = cPermission::checkAdminPermission(cRegistry::getAuth()->getPerms());

$idArtLangVersion = $_REQUEST['idArtLangVersion'] ?? '';

if (isset($idart) && $idartlang <= 0) {
    $oArtLangColl = new cApiArticleLanguageCollection();
    $idartlang = cSecurity::toInteger($oArtLangColl->getIdByArticleIdAndLanguageId($idart, $lang));
}

$selectedArticleId = !empty($idArtLangVersion) ? $idArtLangVersion : NULL;

$versioning = new cContentVersioning();
$versioningState = $versioning->getState();
$articleType = $versioning->getArticleType(
    $idArtLangVersion, $idartlang, $action, $selectedArticleId
);

$versioningElement = '';

switch ($versioningState) {
    case $versioning::STATE_SIMPLE:
        if ($action == 'copyto') {
            if (is_numeric($idArtLangVersion)) {
                $artLangVersion = new cApiArticleLanguageVersion(cSecurity::toInteger($idArtLangVersion));
                $artLangVersion->markAsCurrent('complete');
                $selectedArticleId = 'current';
            }
        }

        $optionElementParameters = $versioning->getDataForSelectElement($idartlang, 'config');
        // Create Article Version Option Elements
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Check if selected version is available, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) as $key => $value) {
            $temp_ids[] = key($value);
        }
        if (!in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable' && $articleType != 'current' && $articleType != 'editable') {
            foreach ($temp_ids as $key => $value) {
                if ($selectedArticleId < $value) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        foreach ($optionElementParameters as $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            //if ($articleType == 'version') {
            //if ($selectedArticleId == key($value)) {
            //$optionElement->setSelected(true);
            //}

            //if ($selectedArticleId < key($value) || $selectedArticleId == key($value)) {
            if ($temp_id == key($value)) {
                $optionElement->setSelected(true);
            }
            //}
            $selectElement->appendOptionElement($optionElement);
        }

        $versioningInfoTextBox = new cGuiBackendHelpbox(i18n(
            '<strong>Simple-mode:</strong>'
            . ' Older article versions can be reviewed and restored (For further configurations please go to'
            . ' Administration/System/System configuration).<br/><br/>'
            . 'Changes are related to article properties, SEO\'s and contents!'
        ));

        // Create markAsCurrent Button
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', i18n('Copy to published version'), 'markAsCurrentButton');
        if ($articleType == 'current' || $articleType == 'editable' && $versioningState == $versioning::STATE_SIMPLE) {
            $markAsCurrentButton->setAttribute('DISABLED');
        }

        // box to select article version
        $versioningBox = new cHTMLTableRow();

        $versionBoxDescription = new cHTMLTableData(i18n("Select Article Version"));
        $versionBoxDescription->setClass('text_medium border_t_b3');
        $versioningBox->appendContent($versionBoxDescription);

        $versionBoxData = new cHTMLTableData();
        $versionBoxData->setClass('text_medium border_t_b3');
        $versionBoxData->setAttribute('colspan', 3);

        $versionBoxData->appendContent($versioning->getVersionSelectionField(
            'con_version_selection_inline',
            $selectElement,
            $markAsCurrentButton,
            $versioningInfoTextBox
        ));
        $versioningBox->appendContent($versionBoxData);

        $versioningElement .= $versioningBox->toHtml();
        $versioningElement .= $versioning->getVersionSelectionFieldJavaScript('con_edit_form');

        break;
    case $versioning::STATE_ADVANCED:
        // Set as current/editable
        if ($action == 'copyto') {
            if (is_numeric($idArtLangVersion) && $articleType == 'current') {
                // editable->current
                $artLangVersion = new cApiArticleLanguageVersion(cSecurity::toInteger($idArtLangVersion));
                $artLangVersion->markAsCurrent('complete');
                $selectedArticleId = 'current';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);

            } elseif (is_numeric($idArtLangVersion) && $articleType == 'editable') {
                // version->editable
                $artLangVersion = new cApiArticleLanguageVersion(cSecurity::toInteger($idArtLangVersion));
                $artLangVersion->markAsEditable('complete');
                $articleType = $versioning->getArticleType(
                    $idArtLangVersion, $idartlang, $action, $selectedArticleId
                );
                $selectedArticleId = 'editable';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);

            } elseif ($idArtLangVersion == 'current') {
                // current->editable
                $artLang = new cApiArticleLanguage($idartlang);
                $artLang->markAsEditable('complete');
                $articleType = $versioning->getArticleType(
                    $idArtLangVersion, $idartlang, $action, $selectedArticleId
                );
                $selectedArticleId = 'editable';

                $artLangVersion = $versioning->createArticleLanguageVersion($artLang->toArray());

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);
            }
        }

        $optionElementParameters = $versioning->getDataForSelectElement($idartlang, 'config');

        // set editable element
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        if (isset($versioning->editableArticleId) || $action == 'con_newart') {
            $optionElement = new cHTMLOptionElement(i18n('Draft'), $versioning->getEditableArticleId($idartlang));
            if ($articleType == 'editable') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
            if (count($optionElementParameters) > 0) {
                unset($optionElementParameters[max(array_keys($optionElementParameters))]);
            }
        }

        // Check if selected version is available, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) as $key => $value) {
            $temp_ids[] = key($value);
        }
        if (!in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable' && $articleType != 'current' && $articleType != 'editable') {
            foreach ($temp_ids as $key => $value) {
                if ($value < $selectedArticleId) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        // Create Metatag Version Option Elements
        if ($action != 'con_newart') {
            $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
            if ($articleType == 'current') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
        }

        foreach ($optionElementParameters as $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            //if ($articleType == 'version') {
            if ($temp_id == key($value)) {
                $optionElement->setSelected(true);
            }
            //}
            $selectElement->appendOptionElement($optionElement);
        }

        // Create markAsCurrent Button
        if ($articleType == 'current' || $articleType == 'version') {
            $buttonTitle = i18n('Copy to draft');
        } elseif ($articleType == 'editable') {
            $buttonTitle = i18n('Publish draft');
        }
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle, 'markAsCurrentButton');
        if ($action == 'con_newart') {
            $markAsCurrentButton->setDisabled(true);
        }

        $versioningInfoTextBox = new cGuiBackendHelpbox(i18n(
            '<strong>Advanced-mode:</strong>  '
            . 'Former article versions can be reviewed and restored. Unpublished drafts can be created.'
            . ' (For further configurations please go to Administration/System/System configuration).<br/><br/>'
            . 'Changes are related to article properties, SEO\'s and contents!'
        ));

        // box to select article version
        $versioningBox = new cHTMLTableRow();

        $versionBoxDescription = new cHTMLTableData(i18n("Select Article Version"));
        $versionBoxDescription->setClass('text_medium border_t_b3');
        $versioningBox->appendContent($versionBoxDescription);

        $versionBoxData = new cHTMLTableData();
        $versionBoxData->setClass('text_medium border_t_b3');
        $versionBoxData->setAttribute('colspan', 3);

        $versionBoxData->appendContent($versioning->getVersionSelectionField(
            'con_version_selection_inline',
            $selectElement,
            $markAsCurrentButton,
            $versioningInfoTextBox
        ));
        $versioningBox->appendContent($versionBoxData);

        $versioningElement .= $versioningBox->toHtml();

        break;
    case $versioning::STATE_DISABLED:
        // Versioning is disabled, don't show version select/copy controls
        break;
    default:
        break;
}

$page->set('s', 'ARTICLE_VERSIONING_BOX', $versioningElement);


// build log view
// ------------------
if ($action == "con_newart" && $newart == true) {
    // New article, no action log available
    $query = [];
} else {
    // receive data
    $conCatColl = new cApiCategoryArticleCollection();
    $catArt = $conCatColl->getFieldsByWhereClause([
        'idcatart'
    ], 'idart=' . $idart);

    $permClause = '';
    if ($perm->isClientAdmin($client, false) === false && $perm->isSysadmin(false) === false) {
        $permClause = " AND user_id = '" . $auth->auth['uid'] . "'";
    }

    $actionCollection = new cApiActionlogCollection();
    $query = $actionCollection->getFieldsByWhereClause([
        'idaction',
        'idlang',
        'idclient',
        'logtimestamp',
        'user_id'
    ], 'idcatart=' . $catArt[0]['idcatart'] . $permClause . ' AND idaction > 0');

    $actionsCollection = new cApiActionCollection();
    $actionsCollection->query();

    $actions = $areas = [];
    while (($actionItem = $actionsCollection->next()) !== false) {
        $actions[$actionItem->get('idaction')] = $actionItem->get('name');
        $areas[$actionItem->get('idaction')] = $classarea->getAreaName($actionItem->get('idarea'));
    }

    // get language id
    $langId = $lang;
    $langItem = new cApiLanguage($langId);
    $language = $langItem->get('name');

    $query = array_reverse($query);
    $query = array_slice($query, 0, 100);

    // set extended values
    foreach ($query as $key => $val) {

        $actionName = $actions[$val['idaction']];
        $areaName = $areas[$val['idaction']];
        $query[$key]['action'] = $lngAct[$areaName][$actionName];
        $user = new cApiUser($val['user_id']);
        $query[$key]['user'] = $user->get('username');

        // if backend language id and log language id are the same set the
        // backend
        // id. Else get the language name from the language id set in action
        // log.
        if ($langId === (int)$val['idlang']) {
            $query[$key]['language'] = $language;
        } else {
            $languageItem = new cApiLanguage($val['idlang']);
            $query[$key]['language'] = $languageItem->get('name');
        }
    }
}

// Article log table
$div = new cHTMLDiv();

// generate table
$table = new cHTMLTable();
$table->setWidth('100%');
$table->setClass('generic');

// build table header
$thead = new cHTMLTableHeader();

$th = new cHTMLTableHead();
$th->setClass('row_1');
$th->setContent(i18n('Language'));

$th2 = new cHTMLTableHead();
$th2->setContent(i18n('User'));
$th2->setClass('row_2');

$th3 = new cHTMLTableHead();
$th3->setContent(i18n('Date'));
$th3->setClass('row_3');

$th4 = new cHTMLTableHead();
$th4->setContent(i18n('Action'));
$th4->setClass('row_4');

$thead->appendContent($th);
$thead->appendContent($th2);
$thead->appendContent($th3);
$thead->appendContent($th4);
$table->appendContent($thead);

// assign values to table
foreach ($query as $key => $val) {
    $tr = new cHTMLTableRow();
    $data = new cHTMLTableData();
    $data->setClass('row_1');
    $data->setContent($val['language']);
    $tr->appendContent($data);

    $data = new cHTMLTableData();
    $data->setClass('row_2');
    $data->setContent($val['user']);
    $tr->appendContent($data);

    $data = new cHTMLTableData();
    $data->setClass('row_3');
    $data->setContent($val['logtimestamp']);
    $tr->appendContent($data);

    $data = new cHTMLTableData();
    $data->setClass('row_4');
    $data->setContent($val['action']);
    $tr->appendContent($data);

    // filter empty action names
    if (!isset($val['action'])) {
        continue;
    } else {
        // append data to table
        $table->appendContent($tr);
    }
}

$div->appendContent($table);
// ------------------

if ($action == "remove_assignments") {
    $oCatArtCol = new cApiCategoryArticleCollection();
    $oCatArtCol->deleteByWhereClause(sprintf('idart = %d AND idcat != %d', $idart, $idcat));
}
if ($action == "con_newart" && $newart != true) {
    // nothing to be done here ?!
    return;
}

if ($versioningState == $versioning::STATE_SIMPLE && $articleType == 'version'
    || $versioningState == $versioning::STATE_ADVANCED && $articleType != 'editable') {
    $disabled = 'disabled="disabled"';
} else {
    $disabled = '';
}

if ($perm->have_perm_area_action($area, "con_edit") || $perm->have_perm_area_action_item($area, "con_edit", $idcat)) {

    // apply settings from the synchronization menu
    // take single articles online or offline

    $inUse = false;

    if (isset($_POST['onlineOne'])) {
        conMakeOnline(cRegistry::getArticleId(), cSecurity::toInteger($_POST['onlineOne']), 1);
    } elseif (isset($_POST['offlineOne'])) {
        conMakeOnline(cRegistry::getArticleId(), cSecurity::toInteger($_POST['offlineOne']), 0);
    }

    // synchronize a single article after checking permissions
    $postSyncOne = cSecurity::toInteger($_POST['syncOne'] ?? '0');
    if ($postSyncOne > 0) {
        $tmpIdcat = cSecurity::toInteger(cRegistry::getCategoryId());
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $tmpIdcatlang = $oCatLangColl->getIdCatLangByIdcatAndIdlang($tmpIdcat, $postSyncOne);
        $isSyncable = cSecurity::toBoolean($tmpIdcatlang);
        if ($isSyncable && (($perm->have_perm_area_action("con", "con_syncarticle") || $perm->have_perm_area_action_item("con", "con_syncarticle", $tmpIdcat)) && ($perm->have_perm_client('lang[' . $postSyncOne . ']') || $perm->have_perm_client('admin[' . cRegistry::getClientId() . ']') || $perm->have_perm_client()))) {
            conSyncArticle(cRegistry::getArticleId(), $lang, cSecurity::toInteger($_POST['syncOne']));
        }
    }

    // take multiple articles online or offline
    $onlineValue = -1;
    if (isset($_POST['offlineAll'])) {
        $onlineValue = 0;
    } elseif (isset($_POST['onlineAll'])) {
        $onlineValue = 1;
    }
    if (isset($_POST['syncingLanguage']) && is_array($_POST['syncingLanguage']) && $onlineValue != -1) {
        foreach ($_POST['syncingLanguage'] as $langId) {
            conMakeOnline(cRegistry::getArticleId(), cSecurity::toInteger($langId), $onlineValue);
        }
    }

    // synchronize multiple articles
    if (isset($_POST['syncAll'])) {
        if (is_array($_POST['syncingLanguage'])) {
            $tmpIdcat = cSecurity::toInteger(cRegistry::getCategoryId());
            $oCatLangColl = new cApiCategoryLanguageCollection();

            foreach ($_POST['syncingLanguage'] as $langId) {
                $langId = cSecurity::toInteger($langId);
                $tmpIdcatlang = $oCatLangColl->getIdCatLangByIdcatAndIdlang($tmpIdcat, $langId);
                $isSyncable = cSecurity::toBoolean($tmpIdcatlang);
                if ($isSyncable && (($perm->have_perm_area_action("con", "con_syncarticle") || $perm->have_perm_area_action_item("con", "con_syncarticle", $tmpIdcat)) && ($perm->have_perm_client('lang[' . $langId . ']') || $perm->have_perm_client('admin[' . cRegistry::getClientId() . ']') || $perm->have_perm_client()))) {
                    conSyncArticle(cRegistry::getArticleId(), $lang, $langId);
                }
            }
        }
    }

    $oCatArtCol = new cApiCategoryArticleCollection();
    $tmp_cat_art = $oCatArtCol->getIdByCategoryIdAndArticleId($idcat, $idart);

    $sql = '';
    if (($versioningState == $versioning::STATE_DISABLED || $versioningState == $versioning::STATE_SIMPLE
            && ($articleType == 'current' || $articleType == 'editable'))
        || $versioningState == $versioning::STATE_ADVANCED && $articleType == 'current') {
        $sql = 'SELECT * FROM `%s` WHERE `idart` = %d AND `idlang` = %d';
        $sql = $db->prepare($sql, $cfg['tab']['art_lang'], $idart, $lang);
    } elseif ($action != 'con_newart' && ($selectedArticleId == 'current' || $selectedArticleId == 'editable')
        || $selectedArticleId == NULL) {
        if (is_numeric($versioning->getEditableArticleId($idartlang))) {
            $sql = 'SELECT * FROM `%s` WHERE `idartlangversion` = %d';
            $sql = $db->prepare($sql, $cfg['tab']['art_lang_version'], $versioning->getEditableArticleId($idartlang));
        }
    } else {
        if (is_numeric($selectedArticleId)) {
            $sql = 'SELECT * FROM `%s` WHERE `idartlangversion` = %d';
            $sql = $db->prepare($sql, $cfg['tab']['art_lang_version'], $selectedArticleId);
        }
    }

    if ($sql != '') {
        $db->query($sql);
        $db->nextRecord();
    }

    $tmp_is_start = isStartArticle($db->f("idartlang"), $idcat, $lang);

    if ($db->f("created")) {

        // ****************** this art was edited before ********************
        $tmp_firstedit = 0;
        $tmp_idartlang = $db->f("idartlang");
        $tmp_page_title = cSecurity::unFilter(stripslashes($db->f("pagetitle")));
        $tmp_idlang = $db->f("idlang");
        $tmp_title = cSecurity::unFilter($db->f("title"));
        $tmp_urlname = cSecurity::unFilter($db->f("urlname"));
        $tmp_artspec = $db->f("artspec");
        $tmp_summary = cSecurity::unFilter($db->f("summary"));
        $tmp_created = $db->f("created");
        $tmp_lastmodified = $db->f("lastmodified");
        $tmp_author = $db->f("author");
        $tmp_modifiedby = !empty($db->f("modifiedby")) ? $db->f("modifiedby") : $db->f("author");
        $tmp_online = $db->f("online");
        $tmp_searchable = $db->f("searchable");
        $tmp_published = $db->f("published");
        $tmp_publishedby = $db->f("publishedby");
        $tmp_datestart = $db->f("datestart");
        $tmp_dateend = $db->f("dateend");
        $tmp_sort = $db->f("artsort");
        $tmp_sitemapprio = $db->f("sitemapprio");
        $tmp_changefreq = $db->f("changefreq");
        $tmp_movetocat = $db->f("time_move_cat");
        $tmp_targetcat = $db->f("time_target_cat");
        $tmp_onlineaftermove = $db->f("time_online_move");
        $tmp_usetimemgmt = $db->f("timemgmt");
        $tmp_locked = $db->f("locked");
        $tmp_redirect_checked = ($db->f("redirect") == '1') ? 'checked' : '';
        $tmp_redirect_url = ($db->f("redirect_url") != '0') ? $db->f("redirect_url") : "http://";
        $tmp_external_redirect_checked = ($db->f("external_redirect") == '1') ? 'checked' : '';
        $tmp_redirect_mode = $db->f('redirect_mode');
        $idtplinput = $db->f("idtplinput");
        $newArtStyle = 'table-row';

        $col = new cApiInUseCollection();

        // Remove all own marks
        $col->removeSessionMarks($sess->id);

        if (false === $isAdmin) {
            if ((($obj = $col->checkMark("article", $tmp_idartlang)) === false || $obj->get("userid") == $auth->auth['uid']) && $tmp_locked != 1) {
                $col->markInUse("article", $tmp_idartlang, $sess->id, $auth->auth["uid"]);
                $inUse = false;
                if ($versioningState == $versioning::STATE_SIMPLE && ($articleType == 'current' || $articleType == 'editable')
                    || $versioningState == $versioning::STATE_ADVANCED && $articleType == 'editable' || $versioningState == $versioning::STATE_DISABLED) {
                    $disabled = '';
                }
                $page->set("s", "REASON", i18n('Save article'));
            } elseif ((($obj = $col->checkMark("article", $tmp_idartlang)) === false || $obj->get("userid") == $auth->auth['uid']) && $tmp_locked == 1) {
                $col->markInUse("article", $tmp_idartlang, $sess->id, $auth->auth["uid"]);
                $inUse = true;
                $disabled = 'disabled="disabled"';
                $page->displayWarning(i18n('This article is currently frozen and can not be edited!'));
                $page->set("s", "REASON", i18n('This article is currently frozen and can not be edited!'));
            } else {
                $vuser = new cApiUser($obj->get("userid"));
                $inUseUser = $vuser->getField("username");
                $inUseUserRealName = $vuser->getField("realname");

                $message = sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName);
                $notification->displayNotification("warning", $message);
                $inUse = true;
                $disabled = 'disabled="disabled"';
                $page->set("s", "REASON", sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName));
            }
        }

    } else {

        // ***************** this art is edited the first time *************

        if (!strHasStartArticle($idcat, $lang)) {
            $tmp_is_start = true;
        }

        $tmp_firstedit = (!$idart) ? 1 : 0; // is needed when input is written to db (update or insert)
        $tmp_idartlang = 0;
        $tmp_page_title = '';
        $tmp_idlang = $lang;
        $tmp_title = '';
        $tmp_urlname = '';
        $tmp_artspec = '';
        $tmp_summary = '';
        $tmp_created = date("Y-m-d H:i:s");
        $tmp_lastmodified = date("Y-m-d H:i:s");
        $tmp_author = '';
        $tmp_modifiedby = '';
        $tmp_online = "0";
        $tmp_searchable = "1";
        $tmp_published = date("Y-m-d H:i:s");
        $tmp_publishedby = '';
        $tmp_datestart = "0000-00-00 00:00:00";
        $tmp_dateend = "0000-00-00 00:00:00";
        $tmp_sort = '';
        $tmp_sitemapprio = '0.5';
        $tmp_changefreq = '';
        $tmp_movetocat = '';
        $tmp_targetcat = '';
        $tmp_onlineaftermove = '';
        $tmp_usetimemgmt = '0';
        $tmp_locked = '0';
        $tmp_redirect_checked = '';
        $tmp_redirect_url = "http://";
        $tmp_external_redirect_checked = '';
        $tmp_redirect_mode = '';
        $newArtStyle = 'none';
    }

    $dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

    $tmp2_created = date($dateformat, strtotime($tmp_created));
    $tmp2_lastmodified = date($dateformat, strtotime($tmp_lastmodified));
    $tmp2_published = date($dateformat, strtotime($tmp_published));

    $page->set('s', 'ACTION', $sess->url("main.php?area=$area&frame=$frame"));
    $page->set('s', 'TMP_FIRSTEDIT', $tmp_firstedit);
    $page->set('s', 'IDART', $idart);
    $page->set('s', 'IDCAT', $idcat);
    $page->set('s', 'IDARTLANG', $tmp_idartlang);
    $page->set('s', 'NEWARTSTYLE', $newArtStyle);

    $breadcrumb = renderBackendBreadcrumb($syncoptions, true, true);
    $page->set('s', 'CATEGORY', $breadcrumb);

    // Title
    $page->set('s', 'TITEL', i18n("Title"));

    // plugin Advanced Mod Rewrite - edit by stese
    $page->set('s', 'URLNAME', i18n("Alias"));
    // end plugin Advanced Mod Rewrite

    $artSpecs = cGetArtSpecs(
        cSecurity::toInteger(cRegistry::getClientId()),
        cSecurity::toInteger(cRegistry::getLanguageId())
    );

    $inputArtSortSelect = new cHTMLSelectELement("artspec", "400px");
    $inputArtSortSelect->setClass("text_medium");
    $availableSpec = 0;
    foreach ($artSpecs as $id => $artSpecItem) {
        if ($artSpecItem['online'] == 1) {
            if (($artSpecItem['artspecdefault'] == 1) && (cString::getStringLength($tmp_artspec) == 0 || $tmp_artspec == 0)) {
                $inputArtSortSelect->appendOptionElement(new cHTMLOptionElement($artSpecItem['artspec'], $id, true));
            } elseif ($id == $tmp_artspec) {
                $inputArtSortSelect->appendOptionElement(new cHTMLOptionElement($artSpecItem['artspec'], $id, true));
            } else {
                $inputArtSortSelect->appendOptionElement(new cHTMLOptionElement($artSpecItem['artspec'], $id));
            }
            $availableSpec++;
        }
    }
    // disable select element if a non-editable version is selected
    if ($versioningState == $versioning::STATE_SIMPLE && $articleType != 'current'
        || $versioningState == $versioning::STATE_ADVANCED && $articleType != 'editable') {
        $inputArtSortSelect->setDisabled(true);
    }
    $tmp_inputArtSort = $inputArtSortSelect->toHtml();

    if ($availableSpec === 0) {
        $tmp_inputArtSort = i18n("No article specifications found!");
    }

    // Path for calendar timepicker
    $page->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

    $page->set('s', 'ARTIKELART', i18n("Article specification"));
    $page->set('s', 'ARTIKELARTSELECT', $tmp_inputArtSort);

    $page->set('s', 'TITEL-FIELD', '<input ' . $disabled . ' type="text" class="text_medium" name="title" value="' . conHtmlSpecialChars($tmp_title) . '">');

    // plugin Advanced Mod Rewrite - edit by stese
    $page->set('s', 'URLNAME-FIELD', '<input ' . $disabled . ' type="text" class="text_medium" name="urlname" value="' . conHtmlSpecialChars($tmp_urlname) . '">');
    // end plugin Advanced Mod Rewrite

    $page->set('s', 'ARTIKELID', "idart");
    $page->set('s', 'ARTID', $idart);

    $page->set('s', 'DIRECTLINKTEXT', i18n("Article link"));

    $select = new cHTMLSelectElement("directlink");
    $select->setEvent("change", "var sVal=this.form.directlink.options[this.form.directlink.options.selectedIndex].value; document.getElementById('linkhint').value = sVal; if(sVal)document.getElementById('linkhintA').style.display='inline-block'; else document.getElementById('linkhintA').style.display='none';");

    $baselink = cRegistry::getFrontendUrl() . "front_content.php?idart=$idart";

    $option[0] = new cHTMLOptionElement(i18n("Select an entry to display link"), '');
    $option[1] = new cHTMLOptionElement(i18n("Article only"), $baselink);
    $option[2] = new cHTMLOptionElement(i18n("Article with category"), $baselink . "&idcat=$idcat");
    $option[3] = new cHTMLOptionElement(i18n("Article with category and language"), $baselink . "&idcat=$idcat&changelang=$lang");
    $option[4] = new cHTMLOptionElement(i18n("Article with language"), $baselink . "&changelang=$lang");

    $select->appendOptionElement($option[0]);
    $select->appendOptionElement($option[1]);
    $select->appendOptionElement($option[2]);
    $select->appendOptionElement($option[3]);
    $select->appendOptionElement($option[4]);

    $append = cApiCecHook::executeAndReturn('Contenido.Backend.AfterArticleLink');
    $append = $append ?? '';
    if (cString::getStringLength($append) === 0) {
        $page->set('s', 'HOOK_AFTERARTICLELINK', '');
    } else {
        $page->set('s', 'HOOK_AFTERARTICLELINK', $append);
    }

    $page->set('s', 'DIRECTLINK', $select->render() . '<br><br><input class="text_medium" type="text" id="linkhint" readonly="readonly"> <input id="linkhintA" type="button" value="' . i18n("open") . '" style="display: none;" onclick="window.open(document.getElementById(\'linkhint\').value);">');

    $page->set('s', 'ZUORDNUNGSID', "idcatart");
    $page->set('s', 'ALLOCID', $tmp_cat_art ? $tmp_cat_art : '&nbsp;');

    // Author (Creator)
    $page->set('s', 'AUTHOR_CREATOR', i18n("Author (Creator)"));
    $oAuthor = new cApiUser();
    $oAuthor->loadUserByUsername($tmp_author);
    if ($oAuthor->values && '' != $oAuthor->get('realname')) {
        $authorRealname = $oAuthor->get('realname');
    } else {
        $authorRealname = '&nbsp';
    }
    $page->set('s', 'AUTOR-ERSTELLUNGS-NAME', $authorRealname . '<input type="hidden" class="bb" name="author" value="' . $auth->auth["uname"] . '">' . '&nbsp;');

    // Author (Modifier)
    $oModifiedBy = new cApiUser();
    $oModifiedBy->loadUserByUsername($tmp_modifiedby);
    if ($oModifiedBy->values && '' != $oModifiedBy->get('realname')) {
        $modifiedByRealname = $oModifiedBy->get('realname');
    } else {
        $modifiedByRealname = '&nbsp';
    }
    $page->set('s', 'AUTOR-AENDERUNG-NAME', $modifiedByRealname);

    // Created
    $tmp_erstellt = ($tmp_firstedit == 1) ? '<input type="hidden" name="created" value="' . date("Y-m-d H:i:s") . '">' : '<input type="hidden" name="created" value="' . $tmp_created . '">';
    $page->set('s', 'ERSTELLT', i18n("Created"));
    $page->set('s', 'ERSTELLUNGS-DATUM', $tmp2_created . $tmp_erstellt);

    // Last modified
    $page->set('s', 'AUTHOR_MODIFIER', i18n("Author (Modifier)"));
    $page->set('s', 'LETZTE-AENDERUNG', i18n("Last modified"));
    $page->set('s', 'AENDERUNGS-DATUM', $tmp2_lastmodified . '<input type="hidden" name="lastmodified" value="' . date("Y-m-d H:i:s") . '">');

    // Publishing date
    $page->set('s', 'PUBLISHING_DATE_LABEL', i18n("Publishing date"));
    if ($tmp_online) {
        $publishingDateTextbox = new cHTMLTextbox('publishing_date', $tmp2_published, 20, 40, 'publishing_date', false, null, '', 'text_medium');
        $publishingDateTextbox->setStyle('width: 130px;');
        if (!($versioningState == $versioning::STATE_SIMPLE && ($articleType == 'current' || $articleType == 'editable')
            || $versioningState == $versioning::STATE_ADVANCED && $articleType == 'editable' || $versioningState == $versioning::STATE_DISABLED)) {
            $publishingDateTextbox->setAttribute('disabled', 'disabled');
        }

        $page->set('s', 'PUBLISHING_DATE', $publishingDateTextbox->render());//var_export($publishingDateTextbox->render());
    } else {
        $descriptionTextDiv = new cHTMLDiv(i18n("not yet published"));
        // set overflow to auto so that user can scroll if translation is too long in current language
        $descriptionTextDiv->setStyle('width: 150px; overflow: auto;');
        $page->set('s', 'PUBLISHING_DATE', $descriptionTextDiv->render());
    }

    // Publisher
    $page->set('s', 'PUBLISHER', i18n("Publisher"));
    $oPublishedBy = new cApiUser();
    $oPublishedBy->loadUserByUsername($tmp_publishedby);
    if ($oPublishedBy->values && '' != $oPublishedBy->get('realname')) {
        $publishedByRealname = $oPublishedBy->get('realname');
    } else {
        $publishedByRealname = '&nbsp';
    }
    $page->set('s', 'PUBLISHER_NAME', '<input type="hidden" name="publishedby" value="' . $auth->auth["uname"] . '">' . $publishedByRealname);

    // Redirect
    $page->set('s', 'WEITERLEITUNG', i18n("Redirect"));
    $page->set('s', 'CHECKBOX', '<input id="checkbox_forwarding" ' . $disabled . ' onclick="document.getElementById(\'redirect_url\').disabled = !this.checked;" type="checkbox" name="redirect" value="1" ' . $tmp_redirect_checked . '>');

    // Redirect - URL
    if ($tmp_redirect_checked != '') {
        $forceDisable = '';
    } else {
        $forceDisable = "disabled";
    }
    $page->set('s', 'URL', '<input type="text" ' . $disabled . ' ' . $forceDisable . ' class="text_medium redirect_url" name="redirect_url" id="redirect_url" value="' . conHtmlSpecialChars($tmp_redirect_url) . '">');

    $page->set('s', 'LABEL_REDIRECT_CODE', i18n("Status code"));

    if (isset($catArt[0]['idcatart']) && $catArt[0]['idcatart'] > 0) {
        $page->set('s', 'LOGTABLE_HEADLINE', '<h3 class="con_article_log_header">' . i18n('Articlelog') . '</h3>');
        $page->set('s', 'LOGTABLE', $div->render());
    } else {
        $page->set('s', 'LOGTABLE_HEADLINE', '');
        $page->set('s', 'LOGTABLE', '');
    }

    $page->set('s', 'DISABLE_SELECT', $forceDisable);

    $option307 = '<option value="temporary">' . i18n("Temporary") . '</option>';
    $option301 = '<option value="permanently">' . i18n("Permanently") . '</option>';
    if ($tmp_redirect_mode === 'temporary') {
        $page->set('s', 'REDIRECT_OPTIONS', $option307 . $option301);
    } else {
        $page->set('s', 'REDIRECT_OPTIONS', $option301 . $option307);
    }

    // Redirect - New window
    if (getEffectiveSetting("articles", "show-new-window-checkbox", "false") == "true") {
        $page->set('s', 'CHECKBOX-NEWWINDOW', '<br><input type="checkbox" ' . $disabled . ' id="external_redirect" name="external_redirect" value="1" ' . $tmp_external_redirect_checked . '><label for="external_redirect">' . i18n("New window") . '</label>');
    } else {
        $page->set('s', 'CHECKBOX-NEWWINDOW', '&nbsp;');
    }

    // Online
    $tmp_ochecked = $tmp_online == 1 ? 'checked="checked"' : '';
    if (($perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat))
        && ($versioningState == $versioning::STATE_SIMPLE && $articleType == ($articleType == 'current' || $articleType == 'editable')
            || $versioningState == $versioning::STATE_ADVANCED && $articleType == 'editable' || $versioningState == $versioning::STATE_DISABLED)) {
        $tmp_ocheck = '<input type="checkbox" ' . $disabled . ' id="online" name="online" value="1" ' . $tmp_ochecked . '>';
    } else {
        $tmp_ocheck = '<input disabled="disabled" type="checkbox" name="" value="1" ' . $tmp_ochecked . '>';
    }
    $page->set('s', 'ONLINE', 'Online');
    $page->set('s', 'ONLINE-CHECKBOX', $tmp_ocheck);

    // Startarticle
    $tmp_start_checked = $tmp_is_start ? 'checked="checked"' : '';
    if (($perm->have_perm_area_action("con", "con_makestart") || $perm->have_perm_area_action_item("con", "con_makestart", $idcat))
        && ($versioningState == $versioning::STATE_SIMPLE && ($articleType == 'current' || $articleType == 'editable')
            || $versioningState == $versioning::STATE_ADVANCED && $articleType == 'editable' || $versioningState == $versioning::STATE_DISABLED)) {
        $tmp_start = '<input ' . $disabled . ' type="checkbox" name="is_start" id="is_start" value="1" ' . $tmp_start_checked . '>';
    } else {
        $tmp_start = '<input disabled="disabled" type="checkbox" name="" value="1" ' . $tmp_start_checked . '>';
    }
    $page->set('s', 'STARTARTIKEL', i18n("Start article"));
    $page->set('s', 'STARTARTIKEL-CHECKBOX', $tmp_start);

    // Searchable / Indexable
    $tmp_searchable_checked = $tmp_searchable == 1 ? 'checked="checked"' : '';
    $tmp_searchable_checkbox = '<input type="checkbox" ' . $disabled . ' id="searchable" name="searchable" value="1" ' . $tmp_searchable_checked . '>';
    $page->set('s', 'SEARCHABLE', i18n('Searchable'));
    $page->set('s', 'SEARCHABLE-CHECKBOX', $tmp_searchable_checkbox);

    // Sortierung
    $page->set('s', 'SORTIERUNG', i18n("Sort key"));
    $page->set('s', 'SORTIERUNG-FIELD', '<input type="text" ' . $disabled . ' class="text_medium" name="artsort" value="' . $tmp_sort . '">');

    // Category select
    // Fetch setting
    $oClient = new cApiClient($client);
    $cValue = $oClient->getProperty("system", "multiassign", true);
    $sValue = getSystemProperty("system", "multiassign");

    $tpl2 = new cTemplate();
    $button = '';
    $moveOK = true;

    if ($cValue == true || $sValue == true) {
        // Multi assign
        $page->set('s', 'NOTIFICATION_SYNCHRON', '');

        $tpl2->set('s', 'ID', 'catsel');
        $tpl2->set('s', 'NAME', 'idcatnew[]');
        $tpl2->set('s', 'CLASS', 'text_medium');
        $tpl2->set('s', 'OPTIONS', 'multiple="multiple" size="14"' . $disabled);
    } else {

        // Get article language in other languages than the current one
        $oArtLangColl = new cApiArticleLanguageCollection();
        $tmpArtLandIds = $oArtLangColl->getIdsByWhereClause(sprintf('`idart` = %d AND `idlang` != %d', $idart, $lang));
        if (count($tmpArtLandIds)) {
            $page->set('s', 'NOTIFICATION_SYNCHRON', '<tr><td colspan="4">' . $notification->returnNotification('warning', i18n("The called article is synchronized. If you want to move it please make sure that the target category of this article exists in all languages.")) . '</td></tr>');
        } else {
            $page->set('s', 'NOTIFICATION_SYNCHRON', '');
        }

        if (count(conGetCategoryAssignments($idart)) > 1) {
            // Old behaviour
            $tpl2 = new cTemplate();
            $tpl2->set('s', 'ID', 'catsel');
            $tpl2->set('s', 'NAME', 'fake[]');
            $tpl2->set('s', 'CLASS', 'text_medium');
            $tpl2->set('s', 'OPTIONS', 'multiple="multiple" size="14" disabled="disabled"');

            $rbutton = new cHTMLButton("removeassignment", i18n("Remove assignments"));

            $boxTitle = i18n("Remove multiple category assignments");
            $boxDescr = i18n("Do you really want to remove the assignments to all categories except the current one?");

            $rbutton->setEvent("click", 'Con.showConfirmation("' . $boxDescr . '", function() { removeAssignments(' . $idart . ',' . $idcat . '); });return false;');
            $button = "<br />" . $rbutton->render();

            $moveOK = false;
        } else {
            $tpl2 = new cTemplate();
            $tpl2->set('s', 'ID', 'catsel');
            $tpl2->set('s', 'NAME', 'idcatnew[]');
            $tpl2->set('s', 'CLASS', 'text_medium');
            $tpl2->set('s', 'OPTIONS', 'size="14" ' . $disabled);
        }
    }

    $tmp_idcat_in_art = [];
    if (isset($tplinputchanged) && $tplinputchanged == 1) {
        $tmp_idcat_in_art[] = $idcatnew;
    } elseif ($idart != 0) {
        // get all idcats that contain art
        $sql = 'SELECT `idcat` FROM `%s` WHERE `idart` = %d';
        $db->query($sql, $cfg['tab']['cat_art'], $idart);
        while ($db->nextRecord()) {
            $tmp_idcat_in_art[] = $db->f("idcat");
        }

        if (!count($tmp_idcat_in_art)) {
            $tmp_idcat_in_art[0] = $idcat;
        }
    } else {
        $tmp_idcat_in_art[0] = $idcat;
    }

    // Start date
    if ($tmp_datestart == "0000-00-00 00:00:00") {
        $page->set('s', 'STARTDATE', '');
    } else {
        $page->set('s', 'STARTDATE', $tmp_datestart);
    }

    // End date
    if ($tmp_dateend == "0000-00-00 00:00:00") {
        $page->set('s', 'ENDDATE', '');
    } else {
        $page->set('s', 'ENDDATE', $tmp_dateend);
    }

    // load the catlang for the cateogry name
    $catlang = new cApiCategoryLanguage();
    $catlang->loadByCategoryIdAndLanguageId(cRegistry::getCategoryId(), $lang);
    // build the synchronization menu
    // select all languages for selected client
    $clientLang = new cApiClientLanguageCollection();
    $clientLang->select("idclient = '" . cRegistry::getClientId() . "'");
    $available_client_ids = $clientLang->getAllIds();

    $languages = new cApiLanguageCollection();
    $languages->select("idlang IN(" . join(', ', $available_client_ids) . ")");

    $langArray = [];
    while (($someLang = $languages->nextAccessible()) !== NULL) {
        $langArray[] = $someLang;
    }

    // Show synchronisation options only for three or more client languages
    if (count($langArray) >= 3 && !($action == "con_newart" && $newart == true)) {
        $page->set("s", "STRUCTURE_COLSPAN", "1");

        $langHTML = "";
        foreach ($langArray as $someLang) {
            // skip the current language
            if ($someLang->get("idlang") == $lang) {
                continue;
            }
            // assign the template rows
            $tpl3 = new cTemplate();
            $tpl3->set("s", "LANG_ID", $someLang->get("idlang"));
            $tpl3->set("s", "LANG_NAME", $someLang->get("name"));

            // find this article in other languages
            $sql = 'SELECT `idartlang`, `online` FROM `%s` WHERE `idart` = %d AND `idlang` = %d';
            $db->query($sql, $cfg['tab']['art_lang'], $idart, $someLang->get("idlang"));
            $db->nextRecord();
            $isOnline = $db->f("online");
            $idOfSyncedArticle = $db->f("idartlang");
            $synced = $db->numRows() > 0;

            // find this category in other languages
            $oCatLangColl = new cApiCategoryLanguageCollection();
            $otherLangIdCatLang = $oCatLangColl->getIdCatLangByIdcatAndIdlang(
                cSecurity::toInteger(cRegistry::getCategoryId()),
                cSecurity::toInteger($someLang->get("idlang"))
            );
            $isSyncable = $otherLangIdCatLang > 0;

            // assign all texts depending on the situation
            // if the article is not synced but the category exists in the target
            // language, display the sync option and grey out the online/offline
            // option
            // if the article is already synced don't display the sync button, but
            // the online/offline button
            $onlineImage = "";
            $onlineText = "";
            $buttonName = "";
            $onlineDisabled = "";
            if ($idOfSyncedArticle > 0) {
                $onlineImage = $cfg['path']['images'] . ($isOnline ? "online.gif" : "offline.gif");
                $onlineText = $isOnline ? i18n("Take the article in this language offline") : i18n("Make the article in this language online");
                $buttonName = $isOnline ? "offlineOne" : "onlineOne";
                $onlineDisabled = "";
            } else {
                $onlineImage = $cfg['path']['images'] . "offline_off.gif";
                $onlineText = sprintf(i18n("There is no synchronized article in the language '%s' to take offline/bring online"), $someLang->get('name'));
                $buttonName = "";
                $onlineDisabled = "disabled";
            }

            if ($isSyncable) {
                $tpl3->set("s", "SYNC_TEXT", $synced ? sprintf(i18n("This article is synchronized to '%s'"), $someLang->get("name")) : sprintf(i18n("Synchronize this article to '%s'"), $someLang->get('name')));
                $tpl3->set("s", "SYNC_IMAGE", $cfg['path']['images'] . "but_sync_art.gif");
                $tpl3->set("s", "SYNC_IMAGE_VISIBLE", $synced ? "hidden" : "visible");
                $tpl3->set("s", "SYNC_DISABLED", $synced ? "disabled" : "");
            } else {
                $tpl3->set("s", "SYNC_TEXT", sprintf(i18n("This article can't be synchronized to '%s' since the category '%s' does not exist in that language."), $someLang->get("name"), $catlang->get("name")));
                $tpl3->set("s", "SYNC_IMAGE", $cfg['path']['images'] . "but_sync_art_off.gif");
                $tpl3->set("s", "SYNC_DISABLED", "disabled");
                $tpl3->set("s", "SYNC_IMAGE_VISIBLE", "visible");
            }
            $tpl3->set("s", "ONLINE_TEXT", $onlineText);
            $tpl3->set("s", "ONLINE_IMAGE", $onlineImage);
            $tpl3->set("s", "ONLINE_DISABLED", $onlineDisabled);
            $tpl3->set("s", "BUTTON_NAME", $buttonName);

            $langHTML .= $tpl3->generate($cfg['path']['templates'] . $cfg['templates']['con_edit_form_synclang'], true);
        }

        $tpl4 = new cTemplate();

        // if there aren't any rows of languages, hide the whole menu
        $tpl4->set("s", "SYNCLANGLIST", $langHTML);
        $tpl4->set("s", "SYNC_MENU_DISPLAY", $langHTML != "" ? "table-row" : "none");

        $infoButton = new cGuiBackendHelpbox(i18n("In this menu you can change the synchronization settings of this article. You will find a list of all available languages and can copy this article to languages that have the category of this article. You can also take already synchronized languages online or offline."));
        $tpl4->set("s", "SYNCLISTINFO", $infoButton->render(true));

        $page->set("s", "SYNC", $tpl4->generate($cfg['path']['templates'] . $cfg['templates']['con_edit_form_sync'], true));

    } else { // Define empty template variable SYNC
        $page->set("s", "SYNC", "");
        $page->set("s", "STRUCTURE_COLSPAN", "3");
    }

    $sql = "SELECT
                A.idcat,
                A.level,
                C.name,
                C.idtplcfg
            FROM
                " . $cfg['tab']['cat_tree'] . " AS A,
                " . $cfg['tab']['cat'] . " AS B,
                " . $cfg['tab']['cat_lang'] . " AS C
            WHERE
                A.idcat = B.idcat AND
                B.idcat = C.idcat AND
                C.idlang = " . cSecurity::toInteger($lang) . " AND
                B.idclient = " . cSecurity::toInteger($client) . "
            ORDER BY
                A.idtree";

    $db->query($sql);

    while ($db->nextRecord()) {
        $spaces = '';

        for ($i = 0; $i < $db->f("level"); $i++) {
            $spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        // Prevent moving articles into categories which have no assigned
        // template
        if ($db->f("idtplcfg") == 0) {
            $tpl2->set('d', 'TITLETAG', ' title="' . i18n("You can not move an article into a category, which does not have an assigned template!") . '"');
            $tpl2->set('d', 'DISABLED', ' disabled');
        } else {
            $tpl2->set('d', 'TITLETAG', '');
            $tpl2->set('d', 'DISABLED', '');
        }

        if (!in_array($db->f("idcat"), $tmp_idcat_in_art)) {
            $tpl2->set('d', 'VALUE', $db->f("idcat"));
            $tpl2->set('d', 'SELECTED', '');
            $tpl2->set('d', 'CAPTION', $spaces . cSecurity::unFilter($db->f("name")));

            $tpl2->next();
        } else {
            $tpl2->set('d', 'VALUE', $db->f("idcat"));
            $tpl2->set('d', 'SELECTED', ' selected="selected"');
            $tpl2->set('d', 'CAPTION', $spaces . cSecurity::unFilter($db->f("name")));
            $tpl2->next();

            if ($moveOK == false) {
                $button .= '<input type="hidden" name="idcatnew[]" value="' . $db->f("idcat") . '">';
            }
        }
    }

    $select = $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['con_edit_form_cat'], true);

    // Struktur
    $page->set('s', 'STRUKTUR', i18n("Category"));
    $page->set('s', 'STRUKTUR-FIELD', $select . $button);

    if (isset($tmp_notification)) {
        $page->set('s', 'NOTIFICATION', '<tr><td colspan="4">' . $tmp_notification . '<br></td></tr>');
    } else {
        $page->set('s', 'NOTIFICATION', '');
    }

    if ((($perm->have_perm_area_action("con", "con_makeonline") ||
                $perm->have_perm_area_action_item("con", "con_makeonline", $idcat)) && $inUse == false)
        && ($versioningState == $versioning::STATE_SIMPLE && ($articleType == 'current' || $articleType == 'editable')
            || $versioningState == $versioning::STATE_ADVANCED && $articleType == 'editable' || $versioningState == $versioning::STATE_DISABLED)) {
        $allow_usetimemgmt = '';
        $page->set('s', 'IS_DATETIMEPICKER_DISABLED', 0);
    } else {
        $allow_usetimemgmt = ' disabled="disabled"';
        $page->set('s', 'IS_DATETIMEPICKER_DISABLED', 1);
    }

    $page->set('s', 'SDOPTS', $allow_usetimemgmt);
    $page->set('s', 'EDOPTS', $allow_usetimemgmt);

    if ($tmp_usetimemgmt == '1') {
        $page->set('s', 'TIMEMGMTCHECKED', 'checked' . $allow_usetimemgmt);
    } else {
        $page->set('s', 'TIMEMGMTCHECKED', $allow_usetimemgmt);
    }

    unset($tpl2);

    // Move to category
    $tpl2 = new cTemplate();
    $tpl2->set('s', 'ID', 'catsel');
    $tpl2->set('s', 'NAME', 'time_target_cat');
    $tpl2->set('s', 'CLASS', 'text_medium categories');
    $tpl2->set('s', 'OPTIONS', 'size="1"' . $allow_usetimemgmt);

    $sql = "SELECT
                A.idcat,
                A.level,
                C.name
            FROM
                " . $cfg['tab']['cat_tree'] . " AS A,
                " . $cfg['tab']['cat'] . " AS B,
                " . $cfg['tab']['cat_lang'] . " AS C
            WHERE
                A.idcat = B.idcat AND
                B.idcat = C.idcat AND
                C.idlang = " . cSecurity::toInteger($lang) . " AND
                B.idclient = " . cSecurity::toInteger($client) . "
            ORDER BY
                A.idtree";

    $db->query($sql);

    while ($db->nextRecord()) {
        $spaces = '';

        for ($i = 0; $i < $db->f("level"); $i++) {
            $spaces .= "&nbsp;&nbsp;";
        }

        if ($db->f("idcat") != $tmp_targetcat) {
            $tpl2->set('d', 'VALUE', $db->f("idcat"));
            $tpl2->set('d', 'SELECTED', '');
            $tpl2->set('d', 'CAPTION', $spaces . cSecurity::unFilter($db->f("name")));
            $tpl2->next();
        } else {
            $tpl2->set('d', 'VALUE', $db->f("idcat"));
            $tpl2->set('d', 'SELECTED', 'selected="selected"');
            $tpl2->set('d', 'CAPTION', $spaces . cSecurity::unFilter($db->f("name")));
            $tpl2->next();
        }
    }

    $select = $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);

    // Seitentitel
    $title_input = '<input type="text" ' . $disabled . ' class="text_medium" name="page_title" value="' . conHtmlSpecialChars($tmp_page_title) . '">';
    $page->set("s", "TITLE-INPUT", $title_input);

    // Struktur
    $page->set('s', 'MOVETOCATEGORYSELECT', $select);

    if ($tmp_movetocat == "1") {
        $page->set('s', 'MOVETOCATCHECKED', 'checked' . $allow_usetimemgmt);
    } else {
        $page->set('s', 'MOVETOCATCHECKED', '' . $allow_usetimemgmt);
    }

    if ($tmp_onlineaftermove == "1") {
        $page->set('s', 'ONLINEAFTERMOVECHECKED', 'checked' . $allow_usetimemgmt);
    } else {
        $page->set('s', 'ONLINEAFTERMOVECHECKED', '' . $allow_usetimemgmt);
    }

    // Summary
    $page->set('s', 'SUMMARY', i18n("Summary"));
    $page->set('s', 'SUMMARY-INPUT', '<textarea ' . $disabled . ' class="text_medium" name="summary" cols="50" rows="5">' . $tmp_summary . '</textarea>');

    $sql = "SELECT
                b.idcat
            FROM
                " . $cfg['tab']['cat'] . " AS a,
                " . $cfg['tab']['cat_lang'] . " AS b,
                " . $cfg['tab']['cat_art'] . " AS c
            WHERE
                a.idclient = " . cSecurity::toInteger($client) . " AND
                a.idcat = b.idcat AND
                c.idcat = b.idcat AND
                c.idart = " . cSecurity::toInteger($idart);

    $db->query($sql);
    $db->nextRecord();

    $midcat = $db->f("idcat");

    if (isset($idart)) {
        if (!isset($idartlang) || 0 == $idartlang) {
            $oArtLangColl = new cApiArticleLanguageCollection();
            $idartlang = $oArtLangColl->getIdByArticleIdAndLanguageId(
                cSecurity::toInteger($idart),
                cSecurity::toInteger($lang)
            );
        }
    }

    if (isset($midcat)) {
        if (!isset($idcatlang) || 0 == $idcatlang) {
            $oCatLangColl = new cApiCategoryLanguageCollection();
            $idcatlang = $oCatLangColl->getIdCatLangByIdcatAndIdlang(
                cSecurity::toInteger($midcat),
                cSecurity::toInteger($lang)
            );
        }
    }

    if (isset($midcat) && isset($idart)) {
        if (!isset($idcatart) || 0 == $idcatart) {
            $oCatArtCol = new cApiCategoryArticleCollection();
            $idcatart = $oCatArtCol->getIdByCategoryIdAndArticleId(
                cSecurity::toInteger($midcat),
                cSecurity::toInteger($idart)
            );
        }
    }

    // provide possibility to add additional rows
    $additionalRows = '';
    $cecRegistry = cApiCecRegistry::getInstance();
    $cecIterator = $cecRegistry->getIterator('Contenido.Backend.ConEditFormAdditionalRows');
    while (($chainEntry = $cecIterator->next()) !== false) {
        $additionalRows .= $chainEntry->execute($idart, $lang, $client);
    }
    $page->set('s', 'ADDITIONAL_ROWS', $additionalRows);

    $script = '';
    if ($newart) {
        $script = 'artObj.disableNavForNewArt();';
    } else {
        $script = 'artObj.enableNavForArt();';
    }
    if (0 != $idart && 0 != $midcat) {
        $script .= 'artObj.setProperties("' . $idart . '", "' . $idartlang . '", "' . $midcat . '", "' . $idcatlang . '", "' . $idcatart . '", "' . $lang . '");';
    } else {
        $script .= 'artObj.reset();';
    }

    $page->set('s', 'DATAPUSH', $script);

    $page->set('s', 'BUTTONDISABLE', $disabled);

    // disable/grey out button if article is in use or a non-editable version is selected
    if ($inUse == true || ($versioningState == $versioning::STATE_SIMPLE && $articleType != 'current'
            || $versioningState == $versioning::STATE_ADVANCED && $articleType != 'editable')) {
        $page->set('s', 'BUTTONIMAGE', 'but_ok_off.gif');
    } else {
        $page->set('s', 'BUTTONIMAGE', 'but_ok.gif');
    }

    if (($lang_short = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != "en") {
        $langscripts = cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/timepicker-' . $lang_short . '.js')) . "\n"
            . cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/datepicker-' . $lang_short . '.js'));
        $page->set('s', 'CAL_LANG', $langscripts);
    } else {
        $page->set('s', 'CAL_LANG', '');
    }

    if ($tmp_usetimemgmt == '1') {
        if ($tmp_datestart == "0000-00-00 00:00:00" && $tmp_dateend == "0000-00-00 00:00:00") {
            $message = sprintf(i18n("Please fill in the start date and/or the end date!"));
            $notification->displayNotification("warning", $message);
        }
    }

    if (isset($bNoArticle)) {
        $page->set('s', 'bNoArticle', $bNoArticle);
    } else {
        $page->set('s', 'bNoArticle', 'false');
    }
    // breadcrumb onclick
    $page->set('s', 'iIdcat', $idcat);
    $page->set('s', 'iIdtpl', $idtpl);
    $page->set('s', 'SYNCOPTIONS', -1);
    $page->set('s', 'DISPLAY_MENU', 1);

    // Generate the template
    $page->render();
} else {
    // User has no permission to see this form
    $notification->displayNotification("error", i18n("Permission denied"));
}
