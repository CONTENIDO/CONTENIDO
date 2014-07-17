<?php
/**
 * This file contains the backend page for displaying and editing article
 * properties.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
cInclude("includes", "functions.tpl.php");
cInclude("includes", "functions.str.php");
cInclude("includes", "functions.pathresolver.php");

// ugly globals that are used in this script
global $tpl, $cfg, $db, $perm, $sess;
global $frame, $area, $action, $contenido, $notification;
global $client, $lang, $belang;
global $idcat, $idart, $idcatlang, $idartlang, $idcatart, $idtpl;
global $tplinputchanged, $idcatnew, $newart, $syncoptions, $tmp_notification, $bNoArticle;

// build log view
// ------------------
if ($action == "con_newart" && $newart == true) {
    // New article, no action log available
    $query = array();
} else {
    // receive data
    $conCatColl = new cApiCategoryArticleCollection();
    $catArt = $conCatColl->getFieldsByWhereClause(array(
        'idcatart'
    ), 'idart=' . $idart);

    $permClause = '';
    if ($perm->isClientAdmin($client, false) === false && $perm->isSysadmin(false) === false) {
        $permClause = " AND user_id = '" . $auth->auth['uid'] . "'";
    }

    $actionCollection = new cApiActionlogCollection();
    $query = $actionCollection->getFieldsByWhereClause(array(
        'idaction',
        'idlang',
        'idclient',
        'logtimestamp',
        'user_id'
    ), 'idcatart=' . $catArt[0]['idcatart'] . $permClause);

    $actionsCollection = new cApiActionCollection();
    $actionsCollection->query();

    $actions = $areas = array();
    while (($actionItem = $actionsCollection->next()) !== false) {
        $actions[$actionItem->get('idaction')] = $actionItem->get('name');
        $areas[$actionItem->get('idaction')] = $classarea->getAreaName($actionItem->get('idarea'));
    }

    // get language id
    $langId = cRegistry::getLanguageId();
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
        if ($langId === (int) $val['idlang']) {
            $query[$key]['language'] = $language;
        } else {
            $languageItem = new cApiLanguage($val['idlang']);
            $query[$key]['language'] = $languageItem->get('name');
        }
    }
}

// <div style="height:200px;overflow-y:scroll;width:700px;">

$div = new cHTMLDiv();
$div->setStyle("height:200px;width:700px;");

// generate table
$table = new cHTMLTable();
$table->setWidth('680px');
$table->setClass('generic');
$table->setId('main-table');

$thead = new cHTMLTableHeader();
$thead->setClass('main-head');
// $tr = new cHTMLTableRow();

// build table header
$th = new cHTMLTableHead();
$th->setClass('first-row');
$th->setContent(i18n('Language'));

$th2 = new cHTMLTableHead();
$th2->setContent(i18n('User'));
$th2->setClass('second-row');

$th3 = new cHTMLTableHead();
$th3->setContent(i18n('Date'));
$th3->setClass('third-row');

$th4 = new cHTMLTableHead();
$th4->setContent(i18n('Action'));
$th4->setClass('fourth-row');

$thead->appendContent($th);
$thead->appendContent($th2);
$thead->appendContent($th3);
$thead->appendContent($th4);
$table->appendContent($thead);

// $table->appendContent($tr);

// assign values to table
foreach ($query as $key => $val) {

    $tr = new cHTMLTableRow();
    $data = new cHTMLTableData();
    $data->setClass('first-row');
    $data->setContent($val['language']);
    $tr->appendContent($data);

    $data = new cHTMLTableData();
    $data->setClass('second-row');
    $data->setContent($val['user']);
    $tr->appendContent($data);

    $data = new cHTMLTableData();
    $data->setClass('third-row');
    $data->setContent($val['logtimestamp']);
    $tr->appendContent($data);

    $data = new cHTMLTableData();
    $data->setClass('fourth-row');
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

$tpl->reset();

if ($action == "remove_assignments") {
    $sql = "DELETE
            FROM
                " . $cfg["tab"]["cat_art"] . "
            WHERE
                idart = " . cSecurity::toInteger($idart) . "
                AND idcat != " . cSecurity::toInteger($idcat);
    $db->query($sql);
}
if ($action == "con_newart" && $newart != true) {
    // nothing to be done here ?!
    return;
}

$disabled = '';

if ($perm->have_perm_area_action($area, "con_edit") || $perm->have_perm_area_action_item($area, "con_edit", $idcat)) {
    $sql = "SELECT
                *
            FROM
                " . $cfg["tab"]["cat_art"] . "
            WHERE
                idart = " . cSecurity::toInteger($idart) . "
                AND idcat = " . cSecurity::toInteger($idcat);
    $db->query($sql);
    $db->nextRecord();

    $tmp_cat_art = $db->f("idcatart");

    $sql = "SELECT
                *
            FROM
                " . $cfg["tab"]["art_lang"] . "
            WHERE
                idart = " . cSecurity::toInteger($idart) . "
                AND idlang = " . cSecurity::toInteger($lang);
    $db->query($sql);
    $db->nextRecord();

    $tmp_is_start = isStartArticle($db->f("idartlang"), $idcat, $lang);

    // apply settings from the synchronization menu
    // take single articles online or offline

    if (isset($_POST['onlineOne'])) {
        $db->query("UPDATE %s SET online=1 WHERE idart = '%s' AND idlang ='%s'", $cfg["tab"]["art_lang"], cRegistry::getArticleId(), cSecurity::toInteger($_POST['onlineOne']));
    } else if (isset($_POST['offlineOne'])) {
        $db->query("UPDATE %s SET online=0 WHERE idart = '%s' AND idlang = '%s'", $cfg["tab"]["art_lang"], cRegistry::getArticleId(), cSecurity::toInteger($_POST['offlineOne']));
    }

    // synchronize a single article after checking permissions
    if (isset($_POST['syncOne'])) {
        $sql = "SELECT
        			idcatlang
        		FROM
        			" . $cfg["tab"]["cat_lang"] . "
			    WHERE
    			    idcat = " . cRegistry::getCategoryId() . "
		            AND idlang = " . cSecurity::toInteger($_POST['syncOne']);
        $db->query($sql);
        $db->next_record();
        $isSyncable = (bool) $db->f("idcatlang");

        if ($isSyncable && (($perm->have_perm_area_action("con", "con_syncarticle") || $perm->have_perm_area_action_item("con", "con_syncarticle", cRegistry::getCategoryId())) && ($perm->have_perm_client('lang[' . cSecurity::toInteger($_POST['syncOne']) . ']') || $perm->have_perm_client('admin[' . cRegistry::getClientId() . ']') || $perm->have_perm_client()))) {
            conSyncArticle(cRegistry::getArticleId(), cRegistry::getLanguageId(), cSecurity::toInteger($_POST['syncOne']));
        }
    }

    // take multiple articles online or offline
    $onlineValue = -1;
    if (isset($_POST['offlineAll'])) {
        $onlineValue = 0;
    } else if (isset($_POST['onlineAll'])) {
        $onlineValue = 1;
    }
    if (is_array($_POST['syncingLanguage']) && $onlineValue != -1) {
        foreach ($_POST['syncingLanguage'] as $langId) {
            $db->query("UPDATE %s SET online=%s WHERE idart = '%s' AND idlang = '%s'", $cfg["tab"]["art_lang"], $onlineValue, cRegistry::getArticleId(), cSecurity::toInteger($langId));
        }
    }

    // synchronize multiple articles
    if (isset($_POST['syncAll'])) {
        if (is_array($_POST['syncingLanguage'])) {
            foreach ($_POST['syncingLanguage'] as $langId) {
                $sql = "SELECT
                			idcatlang
                		FROM
                			" . $cfg["tab"]["cat_lang"] . "
        			    WHERE
            			    idcat = " . cRegistry::getCategoryId() . "
        		            AND idlang = " . cSecurity::toInteger($langId);
                $db->query($sql);
                $db->next_record();
                $isSyncable = (bool) $db->f("idcatlang");

                if ($isSyncable && (($perm->have_perm_area_action("con", "con_syncarticle") || $perm->have_perm_area_action_item("con", "con_syncarticle", cRegistry::getCategoryId())) && ($perm->have_perm_client('lang[' . cSecurity::toInteger($langId) . ']') || $perm->have_perm_client('admin[' . cRegistry::getClientId() . ']') || $perm->have_perm_client()))) {
                    conSyncArticle(cRegistry::getArticleId(), cRegistry::getLanguageId(), cSecurity::toInteger($langId));
                }
            }
        }
    }

    if ($db->f("created")) {

        // ****************** this art was edited before ********************
        $tmp_firstedit = 0;
        $tmp_idartlang = $db->f("idartlang");
        $tmp_page_title = cSecurity::unFilter(stripslashes($db->f("pagetitle")));
        $tmp_idlang = $db->f("idlang");
        $tmp_title = cSecurity::unFilter($db->f("title"));
        // plugin Advanced Mod Rewrite - edit by stese
        $tmp_urlname = cSecurity::unFilter($db->f("urlname"));
        $tmp_artspec = $db->f("artspec");
        $tmp_summary = cSecurity::unFilter($db->f("summary"));
        $tmp_created = $db->f("created");
        $tmp_lastmodified = $db->f("lastmodified");
        $tmp_author = $db->f("author");
        $tmp_modifiedby = $db->f("modifiedby");
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
        $tmp_redirect_checked = ($db->f("redirect") == '1')? 'checked' : '';
        $tmp_redirect_url = ($db->f("redirect_url") != '0')? $db->f("redirect_url") : "http://";
        $tmp_external_redirect_checked = ($db->f("external_redirect") == '1')? 'checked' : '';
        $tmp_redirect_mode = $db->f('redirect_mode');
        $idtplinput = $db->f("idtplinput");

        if ($tmp_modifiedby == '') {
            $tmp_modifiedby = $tmp_author;
        }

        $col = new cApiInUseCollection();

        // Remove all own marks
        $col->removeSessionMarks($sess->id);

        if ((($obj = $col->checkMark("article", $tmp_idartlang)) === false || $obj->get("userid") == $auth->auth['uid']) && $tmp_locked != 1) {
            $col->markInUse("article", $tmp_idartlang, $sess->id, $auth->auth["uid"]);
            $inUse = false;
            $disabled = '';
            $tpl->set("s", "REASON", i18n('Save article'));
        } else if ((($obj = $col->checkMark("article", $tmp_idartlang)) === false || $obj->get("userid") == $auth->auth['uid']) && $tmp_locked == 1) {
            $col->markInUse("article", $tmp_idartlang, $sess->id, $auth->auth["uid"]);
            $inUse = true;
            $disabled = 'disabled="disabled"';
            $notification->displayNotification('warning', i18n('This article is currently frozen and can not be edited!'));
            $tpl->set("s", "REASON", i18n('This article is currently frozen and can not be edited!'));
        } else {
            $vuser = new cApiUser($obj->get("userid"));
            $inUseUser = $vuser->getField("username");
            $inUseUserRealName = $vuser->getField("realname");

            $message = sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName);
            $notification->displayNotification("warning", $message);
            $inUse = true;
            $disabled = 'disabled="disabled"';
            $tpl->set("s", "REASON", sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName));
        }

        $newArtStyle = 'table-row';
    } else {

        // ***************** this art is edited the first time *************

        if (!$idart) {
            $tmp_firstedit = 1; // **** is needed when input is written to db
                                    // (update or insert)
        }

        $tmp_idartlang = 0;
        $tmp_idlang = $lang;
        $tmp_page_title = stripslashes($db->f("pagetitle"));
        $tmp_title = '';
        $tmp_urlname = ''; // plugin Advanced Mod Rewrite - edit by stese
        $tmp_artspec = '';
        $tmp_summary = '';
        $tmp_created = date("Y-m-d H:i:s");
        $tmp_lastmodified = date("Y-m-d H:i:s");
        $tmp_published = date("Y-m-d H:i:s");
        $tmp_publishedby = '';
        $tmp_author = '';
        $tmp_online = "0";
        $tmp_searchable = "1";
        $tmp_datestart = "0000-00-00 00:00:00";
        $tmp_dateend = "0000-00-00 00:00:00";
        $tmp_keyart = '';
        $tmp_keyautoart = '';
        $tmp_sort = '';
        $tmp_sitemapprio = '0.5';
        $tmp_changefreq = '';

        if (!strHasStartArticle($idcat, $lang)) {
            $tmp_is_start = true;
        }

        $tmp_redirect_checked = '';
        $tmp_redirect_url = "http://";
        $tmp_external_redirect = '';
        $newArtStyle = 'none';
    }

    $dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

    $tmp2_created = date($dateformat, strtotime($tmp_created));
    $tmp2_lastmodified = date($dateformat, strtotime($tmp_lastmodified));
    $tmp2_published = date($dateformat, strtotime($tmp_published));

    $tpl->set('s', 'ACTION', $sess->url("main.php?area=$area&frame=$frame&action=con_saveart&idart=$idart"));
    $tpl->set('s', 'TMP_FIRSTEDIT', $tmp_firstedit);
    $tpl->set('s', 'IDART', $idart);
    $tpl->set('s', 'IDCAT', $idcat);
    $tpl->set('s', 'IDARTLANG', $tmp_idartlang);
    $tpl->set('s', 'NEWARTSTYLE', $newArtStyle);
    // $tpl->set('s', $db->f('redirect_code'));

    $hiddenfields = '<input type="hidden" name="idcat" value="' . $idcat . '">
                     <input type="hidden" name="idart" value="' . $idart . '">
                     <input type="hidden" name="send" value="1">';

    $tpl->set('s', 'HIDDENFIELDS', $hiddenfields);

    $breadcrumb = renderBackendBreadcrumb($syncoptions, true, true);
    $tpl->set('s', 'CATEGORY', $breadcrumb);

    // Title
    $tpl->set('s', 'TITEL', i18n("Title"));

    // plugin Advanced Mod Rewrite - edit by stese
    $tpl->set('s', 'URLNAME', i18n("Alias"));
    // end plugin Advanced Mod Rewrite

    $arrArtSpecs = getArtSpec();

    $inputArtSortSelect = new cHTMLSelectELement("artspec", "400ox");
    $inputArtSortSelect->setClass("text_medium");
    $iAvariableSpec = 0;
    foreach ($arrArtSpecs as $id => $value) {
        if ($arrArtSpecs[$id]['online'] == 1) {
            if (($arrArtSpecs[$id]['default'] == 1) && (strlen($tmp_artspec) == 0 || $tmp_artspec == 0)) {
                $inputArtSortSelect->appendOptionElement(new cHTMLOptionElement($arrArtSpecs[$id]['artspec'], $id, true));
            } elseif ($id == $tmp_artspec) {
                $inputArtSortSelect->appendOptionElement(new cHTMLOptionElement($arrArtSpecs[$id]['artspec'], $id, true));
            } else {
                $inputArtSortSelect->appendOptionElement(new cHTMLOptionElement($arrArtSpecs[$id]['artspec'], $id));
            }
            $iAvariableSpec++;
        }
    }
    $tmp_inputArtSort .= $inputArtSortSelect->toHTML();

    if ($iAvariableSpec == 0) {
        $tmp_inputArtSort = i18n("No article specifications found!");
    }

    // Path for calendar timepicker
    $tpl->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

    $tpl->set('s', 'ARTIKELART', i18n("Article specification"));
    $tpl->set('s', 'ARTIKELARTSELECT', $tmp_inputArtSort);

    $tpl->set('s', 'TITEL-FIELD', '<input ' . $disabled . ' type="text" class="text_medium" name="title" value="' . conHtmlSpecialChars($tmp_title) . '">');

    // plugin Advanced Mod Rewrite - edit by stese
    $tpl->set('s', 'URLNAME-FIELD', '<input ' . $disabled . ' type="text" class="text_medium" name="urlname" value="' . conHtmlSpecialChars($tmp_urlname) . '">');
    // end plugin Advanced Mod Rewrite

    $tpl->set('s', 'ARTIKELID', "idart");
    $tpl->set('s', 'ARTID', $idart);

    $tpl->set('s', 'DIRECTLINKTEXT', i18n("Article link"));

    $select = new cHTMLSelectElement("directlink");
    $select->setEvent("change", "var sVal=this.form.directlink.options[this.form.directlink.options.selectedIndex].value; document.getElementById('linkhint').value = sVal; if(sVal)document.getElementById('linkhintA').style.display='inline-block'; else document.getElementById('linkhintA').style.display='none';");
    if (cSecurity::toInteger($idart) == 0) {
        $select->setEvent("disabled", "disabled");
    }

    $baselink = cRegistry::getFrontendUrl() . "front_content.php?idart=$idart";

    $option[0] = new cHTMLOptionElement(i18n("Select an entry to display link"), '');
    $option[1] = new cHTMLOptionElement(i18n("Article only"), $baselink);
    $option[2] = new cHTMLOptionElement(i18n("Article with category"), $baselink . "&idcat=$idcat");
    $option[3] = new cHTMLOptionElement(i18n("Article with category and language"), $baselink . "&idcat=$idcat&lang=$lang");
    $option[4] = new cHTMLOptionElement(i18n("Article with language"), $baselink . "&lang=$lang");

    $select->appendOptionElement($option[0]);
    $select->appendOptionElement($option[1]);
    $select->appendOptionElement($option[2]);
    $select->appendOptionElement($option[3]);
    $select->appendOptionElement($option[4]);

    $tpl->set('s', 'DIRECTLINK', $select->render() . '<br><br><input class="text_medium" type="text" id="linkhint" readonly="readonly" ' . $disabled . '> <input id="linkhintA" type="button" value="' . i18n("open") . '" style="display: none;" onclick="window.open(document.getElementById(\'linkhint\').value);">');

    $tpl->set('s', 'ZUORDNUNGSID', "idcatart");
    $tpl->set('s', 'ALLOCID', $tmp_cat_art? $tmp_cat_art : '&nbsp;');

    // Author (Creator)
    $tpl->set('s', 'AUTHOR_CREATOR', i18n("Author (Creator)"));
    $oAuthor = new cApiUser();
    $oAuthor->loadUserByUsername($tmp_author);
    if ($oAuthor->values && '' != $oAuthor->get('realname')) {
        $authorRealname = $oAuthor->get('realname');
    } else {
        $authorRealname = '&nbsp';
    }
    $tpl->set('s', 'AUTOR-ERSTELLUNGS-NAME', $authorRealname . '<input type="hidden" class="bb" name="author" value="' . $auth->auth["uname"] . '">' . '&nbsp;');

    // Author (Modifier)
    $oModifiedBy = new cApiUser();
    $oModifiedBy->loadUserByUsername($tmp_modifiedby);
    if ($oModifiedBy->values && '' != $oModifiedBy->get('realname')) {
        $modifiedByRealname = $oModifiedBy->get('realname');
    } else {
        $modifiedByRealname = '&nbsp';
    }
    $tpl->set('s', 'AUTOR-AENDERUNG-NAME', $modifiedByRealname);

    // Created
    $tmp_erstellt = ($tmp_firstedit == 1)? '<input type="hidden" name="created" value="' . date("Y-m-d H:i:s") . '">' : '<input type="hidden" name="created" value="' . $tmp_created . '">';
    $tpl->set('s', 'ERSTELLT', i18n("Created"));
    $tpl->set('s', 'ERSTELLUNGS-DATUM', $tmp2_created . $tmp_erstellt);

    // Last modified
    $tpl->set('s', 'AUTHOR_MODIFIER', i18n("Author (Modifier)"));
    $tpl->set('s', 'LETZTE-AENDERUNG', i18n("Last modified"));
    $tpl->set('s', 'AENDERUNGS-DATUM', $tmp2_lastmodified . '<input type="hidden" name="lastmodified" value="' . date("Y-m-d H:i:s") . '">');

    // Publishing date
    $tpl->set('s', 'PUBLISHING_DATE_LABEL', i18n("Publishing date"));
    if ($tmp_online) {
        $tpl->set('s', 'PUBLISHING_DATE', $tmp2_published);
    } else {
        $tpl->set('s', 'PUBLISHING_DATE', i18n("not yet published"));
    }

    // Publisher
    $tpl->set('s', 'PUBLISHER', i18n("Publisher"));
    $oPublishedBy = new cApiUser();
    $oPublishedBy->loadUserByUsername($tmp_publishedby);
    if ($oPublishedBy->values && '' != $oPublishedBy->get('realname')) {
        $publishedByRealname = $oPublishedBy->get('realname');
    } else {
        $publishedByRealname = '&nbsp';
    }
    $tpl->set('s', 'PUBLISHER_NAME', '<input type="hidden" class="bb" name="publishedby" value="' . $auth->auth["uname"] . '">' . $publishedByRealname);

    // Redirect
    $tpl->set('s', 'WEITERLEITUNG', i18n("Redirect"));
    $tpl->set('s', 'CHECKBOX', '<input id="checkbox_forwarding" ' . $disabled . ' onclick="document.getElementById(\'redirect_url\').disabled = !this.checked;" type="checkbox" name="redirect" value="1" ' . $tmp_redirect_checked . '>');

    // Redirect - URL
    if ($tmp_redirect_checked != '') {
        $forceDisable = '';
    } else {
        $forceDisable = "disabled";
    }
    $tpl->set('s', 'URL', '<input type="text" ' . $disabled . ' ' . $forceDisable . ' class="text_medium redirectURL" name="redirect_url" id="redirect_url" value="' . conHtmlSpecialChars($tmp_redirect_url) . '">');

    $tpl->set('s', 'LABEL_REDIRECT_CODE', i18n("Status code"));

    if ($catArt[0]['idcatart'] > 0) {
        $tpl->set('s', 'LOGTABLE_HEADLINE', '<h3 style="margin-top:20px;margin-bottom:10px;">' . i18n('Articlelog') . '</h3>');
        $tpl->set('s', 'LOGTABLE', $div->render());
    } else {
        $tpl->set('s', 'LOGTABLE_HEADLINE', '');
        $tpl->set('s', 'LOGTABLE', '');
    }

    $tpl->set('s', 'DISABLE_SELECT', $forceDisable);

    $option307 = '<option value="temporary">' . i18n("Temporary") . '</option>';
    $option301 = '<option value="permanently">' . i18n("Permanently") . '</option>';
    if ($tmp_redirect_mode === 'temporary') {
        $tpl->set('s', 'REDIRECT_OPTIONS', $option307 . $option301);
    } else {
        $tpl->set('s', 'REDIRECT_OPTIONS', $option301 . $option307);
    }

    // Redirect - New window
    if (getEffectiveSetting("articles", "show-new-window-checkbox", "false") == "true") {
        $tpl->set('s', 'CHECKBOX-NEWWINDOW', '<input type="checkbox" ' . $disabled . ' id="external_redirect" name="external_redirect" value="1" ' . $tmp_external_redirect_checked . '></td><td><label for="external_redirect">' . i18n("New window") . '</label>');
    } else {
        $tpl->set('s', 'CHECKBOX-NEWWINDOW', '&nbsp;');
    }

    // Online
    $tmp_ochecked = $tmp_online == 1? 'checked="checked"' : '';
    if ($perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)) {
        $tmp_ocheck = '<input type="checkbox" ' . $disabled . ' id="online" name="online" value="1" ' . $tmp_ochecked . '>';
    } else {
        $tmp_ocheck = '<input disabled="disabled" type="checkbox" name="" value="1" ' . $tmp_ochecked . '>';
    }
    $tpl->set('s', 'ONLINE', 'Online');
    $tpl->set('s', 'ONLINE-CHECKBOX', $tmp_ocheck);

    // Startarticle
    $tmp_start_checked = $tmp_is_start? 'checked="checked"' : '';
    if ($perm->have_perm_area_action("con", "con_makestart") || $perm->have_perm_area_action_item("con", "con_makestart", $idcat)) {
        $tmp_start = '<input ' . $disabled . ' type="checkbox" name="is_start" id="is_start" value="1" ' . $tmp_start_checked . '>';
    } else {
        $tmp_start = '<input disabled="disabled" type="checkbox" name="" value="1" ' . $tmp_start_checked . '>';
    }
    $tpl->set('s', 'STARTARTIKEL', i18n("Start article"));
    $tpl->set('s', 'STARTARTIKEL-CHECKBOX', $tmp_start);

    // Searchable / Indexable
    $tmp_searchable_checked = $tmp_searchable == 1? 'checked="checked"' : '';
    $tmp_searchable_checkbox = '<input type="checkbox" ' . $disabled . ' id="searchable" name="searchable" value="1" ' . $tmp_searchable_checked . '>';
    $tpl->set('s', 'SEARCHABLE', i18n('Searchable'));
    $tpl->set('s', 'SEARCHABLE-CHECKBOX', $tmp_searchable_checkbox);

    // Sortierung
    $tpl->set('s', 'SORTIERUNG', i18n("Sort key"));
    $tpl->set('s', 'SORTIERUNG-FIELD', '<input type="text" ' . $disabled . ' class="text_medium" name="artsort" value="' . $tmp_sort . '">');

    // Category select
    // Fetch setting
    $oClient = new cApiClient($client);
    $cValue = $oClient->getProperty("system", "multiassign", true);
    $sValue = getSystemProperty("system", "multiassign", true);

    $tpl2 = new cTemplate();
    $button = '';
    $moveOK = true;

    if ($cValue == true || $sValue == true) {
        // Multi assign
        $tpl->set('s', 'NOTIFICATION_SYNCHRON', '');

        $tpl2->set('s', 'ID', 'catsel');
        $tpl2->set('s', 'NAME', 'idcatnew[]');
        $tpl2->set('s', 'CLASS', 'text_medium');
        $tpl2->set('s', 'OPTIONS', 'multiple="multiple" size="14"' . $disabled);
    } else {
        $sql = "SELECT
                    idartlang
                FROM
                    " . $cfg["tab"]["art_lang"] . "
                WHERE
                    idart = " . cSecurity::toInteger($idart) . "
                    AND idlang != " . cSecurity::toInteger($lang);
        $db->query($sql);

        if ($db->numRows() > 0) {
            $tpl->set('s', 'NOTIFICATION_SYNCHRON', '<tr><td colspan="4">' . $notification->returnNotification('warning', i18n("The called article is synchronized. If you want to move it please make sure that the target category of this article exist in all languages.")) . '</td></tr>');
        } else {
            $tpl->set('s', 'NOTIFICATION_SYNCHRON', '');
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

    if (isset($tplinputchanged) && $tplinputchanged == 1) {
        $tmp_idcat_in_art = $idcatnew;
    } else {
        // get all idcats that contain art
        $sql = "SELECT
                    idcat
                FROM
                    " . $cfg["tab"]["cat_art"] . "
                WHERE
                    idart = " . cSecurity::toInteger($idart);
        $db->query($sql);
        while ($db->nextRecord()) {
            $tmp_idcat_in_art[] = $db->f("idcat");
        }

        if (!is_array($tmp_idcat_in_art)) {
            $tmp_idcat_in_art[0] = $idcat;
        }
    }

    // Start date
    if ($tmp_datestart == "0000-00-00 00:00:00") {
        $tpl->set('s', 'STARTDATE', '');
    } else {
        $tpl->set('s', 'STARTDATE', $tmp_datestart);
    }

    // End date
    if ($tmp_dateend == "0000-00-00 00:00:00") {
        $tpl->set('s', 'ENDDATE', '');
    } else {
        $tpl->set('s', 'ENDDATE', $tmp_dateend);
    }

    // load the catlang for the cateogry name
    $catlang = new cApiCategoryLanguage();
    $catlang->loadByCategoryIdAndLanguageId(cRegistry::getCategoryId(), cRegistry::getLanguageId());
    // build the synchronization menu
    // select all languages
    $languages = new cApiLanguageCollection();
    $languages->select();
    $langHTML = "";
    while (($someLang = $languages->nextAccessible()) != false) {
        // skip the current language
        if ($someLang->get("idlang") == cRegistry::getLanguageId()) {
            continue;
        }
        // assign the template rows
        $tpl3 = new cTemplate();
        $tpl3->set("s", "LANG_ID", $someLang->get("idlang"));
        $tpl3->set("s", "LANG_NAME", $someLang->get("name"));

        // find this article in other languages
        $sql = "SELECT
                    idartlang, online
                FROM
                    " . $cfg["tab"]["art_lang"] . "
                WHERE
                    idart = " . cSecurity::toInteger($idart) . "
                    AND idlang = " . cSecurity::toInteger($someLang->get("idlang"));
        $db->query($sql);
        $db->next_record();
        $isOnline = $db->f("online");
        $idOfSyncedArticle = $db->f("idartlang");
        $synced = $db->numRows() > 0;

        // find this category in other languages
        $sql = "SELECT
        			idcatlang
        		FROM
        			" . $cfg["tab"]["cat_lang"] . "
			    WHERE
    			    idcat = " . cRegistry::getCategoryId() . "
		            AND idlang = " . cSecurity::toInteger($someLang->get("idlang"));
        $db->query($sql);
        $db->next_record();
        $isSyncable = (bool) $db->f("idcatlang");

        // assign all texts depending on the situation
        // if the article is not synced but the category exists in the target
        // language, display the sync option and grey out the online/offline
        // option
        // if the article is already synced don't display the sync button, but
        // the online/offline button
        $onlineImage = "";
        $onlineText = "";
        $buttonName = "";
        if ($idOfSyncedArticle > 0) {
            $onlineImage = $isOnline? $cfg['path']['images'] . "online.gif" : $cfg['path']['images'] . "offline.gif";
            $onlineText = $isOnline? i18n("Take the article in this language offline") : i18n("Make the article in this language online");
            $buttonName = $isOnline? "offlineOne" : "onlineOne";
        } else {
            $onlineImage = $cfg['path']['images'] . "offline_off.gif";
            $onlineText = sprintf(i18n("There is no synchronized article in the language '%s' to take offline/bring online"), $someLang->get('name'));
            $buttonName = "";
        }

        if ($isSyncable) {
            $tpl3->set("s", "SYNC_TEXT", $synced? sprintf(i18n("This article is synchronized to '%s'"), $someLang->get("name")) : sprintf(i18n("Synchronize this article to '%s'"), $someLang->get('name')));
            $tpl3->set("s", "SYNC_IMAGE", $cfg['path']['images'] . "but_sync_art.gif");
            $tpl3->set("s", "SYNC_IMAGE_VISIBLE", $synced? "hidden" : "visible");
        } else {
            $tpl3->set("s", "SYNC_TEXT", sprintf(i18n("This article can't be synchronized to '%s' since the category '%s' does not exist in that language."), $someLang->get("name"), $catlang->get("name")));
            $tpl3->set("s", "SYNC_IMAGE", $cfg['path']['images'] . "but_sync_art_off.gif");
        }
        $tpl3->set("s", "ONLINE_TEXT", $onlineText);
        $tpl3->set("s", "ONLINE_IMAGE", $onlineImage);
        $tpl3->set("s", "BUTTON_NAME", $buttonName);

        $langHTML .= $tpl3->generate($cfg['path']['templates'] . $cfg['templates']['con_edit_form_synclang'], true);
    }
    // if there aren't any rows of languages, hide the whole menu
    $tpl->set("s", "SYNCLANGLIST", $langHTML);
    $tpl->set("s", "SYNC_MENU_DISPLAY", $langHTML != ""? "table-row" : "none");

    $infoButton = new cGuiBackendHelpbox(i18n("In this menu you can change the synchronization settings of this article. You will find a list of all available languages and can copy this article to languages that have the category of this article. You can also take already synchronized languages online or offline."));
    $tpl->set("s", "SYNCLISTINFO", $infoButton->render(true));

    $sql = "SELECT
                A.idcat,
                A.level,
                C.name,
    			C.idtplcfg
            FROM
                " . $cfg["tab"]["cat_tree"] . " AS A,
                " . $cfg["tab"]["cat"] . " AS B,
                " . $cfg["tab"]["cat_lang"] . " AS C
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

    $select = $tpl2->generate($cfg["path"]["templates"] . $cfg["templates"]["con_edit_form_cat"], true);

    // Struktur
    $tpl->set('s', 'STRUKTUR', i18n("Category"));
    $tpl->set('s', 'STRUKTUR-FIELD', $select . $button);

    if (isset($tmp_notification)) {
        $tpl->set('s', 'NOTIFICATION', '<tr><td colspan="4">' . $tmp_notification . '<br></td></tr>');
    } else {
        $tpl->set('s', 'NOTIFICATION', '');
    }

    if (($perm->have_perm_area_action("con", "con_makeonline") || $perm->have_perm_area_action_item("con", "con_makeonline", $idcat)) && $inUse == false) {
        $allow_usetimemgmt = '';
        $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 0);
    } else {
        $allow_usetimemgmt = ' disabled="disabled"';
        $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 1);
    }

    $tpl->set('s', 'SDOPTS', $allow_usetimemgmt);
    $tpl->set('s', 'EDOPTS', $allow_usetimemgmt);

    if ($tmp_usetimemgmt == '1') {
        $tpl->set('s', 'TIMEMGMTCHECKED', 'checked' . $allow_usetimemgmt);
    } else {
        $tpl->set('s', 'TIMEMGMTCHECKED', $allow_usetimemgmt);
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
                " . $cfg["tab"]["cat_tree"] . " AS A,
                " . $cfg["tab"]["cat"] . " AS B,
                " . $cfg["tab"]["cat_lang"] . " AS C
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

    $select = $tpl2->generate($cfg["path"]["templates"] . $cfg["templates"]["generic_select"], true);

    // Seitentitel
    $title_input = '<input type="text" ' . $disabled . ' class="text_medium" name="page_title" value="' . conHtmlSpecialChars($tmp_page_title) . '">';
    $tpl->set("s", "TITLE-INPUT", $title_input);

    // Struktur
    $tpl->set('s', 'MOVETOCATEGORYSELECT', $select);

    if ($tmp_movetocat == "1") {
        $tpl->set('s', 'MOVETOCATCHECKED', 'checked' . $allow_usetimemgmt);
    } else {
        $tpl->set('s', 'MOVETOCATCHECKED', '' . $allow_usetimemgmt);
    }

    if ($tmp_onlineaftermove == "1") {
        $tpl->set('s', 'ONLINEAFTERMOVECHECKED', 'checked' . $allow_usetimemgmt);
    } else {
        $tpl->set('s', 'ONLINEAFTERMOVECHECKED', '' . $allow_usetimemgmt);
    }

    // Summary
    $tpl->set('s', 'SUMMARY', i18n("Summary"));
    $tpl->set('s', 'SUMMARY-INPUT', '<textarea ' . $disabled . ' class="text_medium" name="summary" cols="50" rows="5">' . $tmp_summary . '</textarea>');

    $sql = "SELECT
                b.idcat
            FROM
                " . $cfg["tab"]["cat"] . " AS a,
                " . $cfg["tab"]["cat_lang"] . " AS b,
                " . $cfg["tab"]["cat_art"] . " AS c
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
            $sql = "SELECT
                        idartlang
                    FROM
                        " . $cfg["tab"]["art_lang"] . "
                    WHERE
                        idart = " . cSecurity::toInteger($idart) . "
                        AND idlang = " . cSecurity::toInteger($lang);
            $db->query($sql);
            $db->nextRecord();
            $idartlang = $db->f("idartlang");
        }
    }

    if (isset($midcat)) {
        if (!isset($idcatlang) || 0 == $idcatlang) {
            $sql = "SELECT
                        idcatlang
                    FROM
                        " . $cfg["tab"]["cat_lang"] . "
                    WHERE
                        idcat = " . cSecurity::toInteger($midcat) . "
                        AND idlang = " . cSecurity::toInteger($lang);
            $db->query($sql);
            $db->nextRecord();
            $idcatlang = $db->f("idcatlang");
        }
    }

    if (isset($midcat) && isset($idart)) {
        if (!isset($idcatart) || 0 == $idcatart) {
            $sql = "SELECT
                        idcatart
                    FROM
                        " . $cfg["tab"]["cat_art"] . "
                    WHERE
                        idart = " . cSecurity::toInteger($idart) . "
                        AND idcat = " . cSecurity::toInteger($midcat);
            $db->query($sql);
            $db->nextRecord();
            $idcatart = $db->f("idcatart");
        }
    }

    // provide possibility to add additional rows
    $additionalRows = '';
    $cecRegistry = cApiCecRegistry::getInstance();
    $cecIterator = $cecRegistry->getIterator('Contenido.Backend.ConEditFormAdditionalRows');
    while (($chainEntry = $cecIterator->next()) !== false) {
        $additionalRows .= $chainEntry->execute($idart, $lang, $client);
    }
    $tpl->set('s', 'ADDITIONAL_ROWS', $additionalRows);

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

    $tpl->set('s', 'DATAPUSH', $script);

    $tpl->set('s', 'BUTTONDISABLE', $disabled);

    if ($inUse == true) {
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok_off.gif');
    } else {
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok.gif');
    }

    if (($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
        $langscripts = '<script type="text/javascript" src="scripts/jquery/plugins/timepicker-' . $lang_short . '.js"></script>
                 <script type="text/javascript" src="scripts/jquery/plugins/datepicker-' . $lang_short . '.js"></script>';
        $tpl->set('s', 'CAL_LANG', $langscripts);
    } else {
        $tpl->set('s', 'CAL_LANG', '');
    }

    if ($tmp_usetimemgmt == '1') {
        if ($tmp_datestart == "0000-00-00 00:00:00" && $tmp_dateend == "0000-00-00 00:00:00") {
            $message = sprintf(i18n("Please fill in the start date and/or the end date!"));
            $notification->displayNotification("warning", $message);
        }
    }

    if (isset($bNoArticle)) {
        $tpl->set('s', 'bNoArticle', $bNoArticle);
    } else {
        $tpl->set('s', 'bNoArticle', 'false');
    }
    // breadcrumb onclick
    $tpl->set('s', 'iIdcat', $idcat);
    $tpl->set('s', 'iIdtpl', $idtpl);
    $tpl->set('s', 'SYNCOPTIONS', -1);
    $tpl->set('s', 'DISPLAY_MENU', 1);

    // Genereate the template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_edit_form']);
} else {
    // User has no permission to see this form
    $notification->displayNotification("error", i18n("Permission denied"));
}
