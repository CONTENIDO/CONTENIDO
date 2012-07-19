<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Form for editing the article properties
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.5.3
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2012-04-30
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.str.php");
cInclude("includes", "functions.pathresolver.php");

$tpl->reset();

if ($action == "remove_assignments") {
    $sql = "DELETE FROM " . $cfg["tab"]["cat_art"] . " WHERE idart=" . cSecurity::toInteger($idart) . " AND idcat != " . cSecurity::toInteger($idcat);
    $db->query($sql);
}
if ($action == "con_newart" && $newart != true) {
    // nothing to be done here ?!

    return;
}

//Add a new Meta Tag in DB
if ($METAmetatype) {
    $sql = "INSERT INTO `" . $cfg["tab"]["meta_type"] . "` (
        `metatype` ,
        `fieldtype` ,
        `maxlength` ,
        `fieldname`
        )
        VALUES (
        '" . $METAmetatype . "', '" . $METAfieldtype . "', '" . $METAmaxlength . "', '" . $METAfieldname . "'
        );";
    $db->query($sql);
}

$disabled = '';

if ($perm->have_perm_area_action($area, "con_meta_edit") ||
        $perm->have_perm_area_action_item($area, "con_meta_edit", $idcat)) {
    $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " WHERE idart=" . cSecurity::toInteger($idart) . " AND idcat=" . cSecurity::toInteger($idcat);
    $db->query($sql);
    $db->next_record();

    $tmp_cat_art = $db->f("idcatart");

    $sql = "SELECT * FROM " . $cfg["tab"]["art_lang"] . " WHERE idart=" . cSecurity::toInteger($idart) . " AND idlang=" . cSecurity::toInteger($lang);
    $db->query($sql);
    $db->next_record();

    $tmp_is_start = isStartArticle($db->f("idartlang"), $idcat, $lang);

    if ($db->f("created")) {

        //****************** this art was edited before ********************
        $tmp_firstedit = 0;
        $tmp_idartlang = $db->f("idartlang");
        $tmp_page_title = cSecurity::unFilter(stripslashes($db->f("pagetitle")));
        $tmp_idlang = $db->f("idlang");
        $tmp_title = cSecurity::unFilter($db->f("title"));
        $tmp_urlname = cSecurity::unFilter($db->f("urlname"));      // plugin Advanced Mod Rewrite - edit by stese
        $tmp_artspec = $db->f("artspec");
        $tmp_summary = cSecurity::unFilter($db->f("summary"));
        $tmp_created = $db->f("created");
        $tmp_lastmodified = $db->f("lastmodified");
        $tmp_author = $db->f("author");
        $tmp_modifiedby = $db->f("modifiedby");
        $tmp_online = $db->f("online");
        $tmp_published = $db->f("published");
        $tmp_publishedby = $db->f("publishedby");
        $tmp_datestart = $db->f("datestart");
        $tmp_dateend = $db->f("dateend");
        $tmp_sort = $db->f("artsort");
        $tmp_movetocat = $db->f("time_move_cat");
        $tmp_targetcat = $db->f("time_target_cat");
        $tmp_onlineaftermove = $db->f("time_online_move");
        $tmp_usetimemgmt = $db->f("timemgmt");
        $tmp_locked = $db->f("locked");
        $tmp_redirect_checked = ($db->f("redirect") == '1') ? 'checked' : '';
        $tmp_redirect_url = ($db->f("redirect_url") != '0') ? $db->f("redirect_url") : "http://";
        $tmp_external_redirect_checked = ($db->f("external_redirect") == '1') ? 'checked' : '';
        $idtplinput = $db->f("idtplinput");

        if ($tmp_modifiedby == '') {
            $tmp_modifiedby = $tmp_author;
        }

        $col = new cApiInUseCollection();

        // Remove all own marks
        $col->removeSessionMarks($sess->id);

        if (($obj = $col->checkMark("article", $tmp_idartlang)) === false || $obj->get("userid") == $auth->auth['uid']) {
            $col->markInUse("article", $tmp_idartlang, $sess->id, $auth->auth["uid"]);
            $inUse = false;
            $disabled = '';
            $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 0);
        } else {
            $vuser = new cApiUser($obj->get("userid"));
            $inUseUser = $vuser->getField("username");
            $inUseUserRealName = $vuser->getField("realname");

            $message = sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName);
            $notification->displayNotification("warning", $message);
            $inUse = true;
            $disabled = 'disabled="disabled"';
            $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 1);
        }

        if ($tmp_locked == 1) {
            $inUse = true;
            $disabled = 'disabled="disabled"';
            $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 1);
        }
    } else {

        //***************** this art is edited the first time *************

        if (!$idart) {
            $tmp_firstedit = 1; //**** is needed when input is written to db (update or insert)
        }

        $tmp_idartlang = 0;
        $tmp_idlang = $lang;
        $tmp_page_title = stripslashes($db->f("pagetitle"));
        $tmp_title = '';
        $tmp_urlname = '';   // plugin Advanced Mod Rewrite - edit by stese
        $tmp_artspec = '';
        $tmp_summary = '';
        $tmp_created = date("Y-m-d H:i:s");
        $tmp_lastmodified = date("Y-m-d H:i:s");
        $tmp_published = date("Y-m-d H:i:s");
        $tmp_publishedby = '';
        $tmp_author = '';
        $tmp_online = "0";
        $tmp_datestart = "0000-00-00 00:00:00";
        $tmp_dateend = "0000-00-00 00:00:00";
        $tmp_keyart = '';
        $tmp_keyautoart = '';
        $tmp_sort = '';

        if (!strHasStartArticle($idcat, $lang)) {
            $tmp_is_start = 1;
        }

        $tmp_redirect_checked = '';
        $tmp_redirect_url = "http://";
        $tmp_external_redirect = '';
    }

    $dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

    $tmp2_created = date($dateformat, strtotime($tmp_created));
    $tmp2_lastmodified = date($dateformat, strtotime($tmp_lastmodified));
    $tmp2_published = date($dateformat, strtotime($tmp_published));

    $tpl->set('s', 'ACTION', $sess->url("main.php?area=$area&frame=$frame&action=con_meta_saveart"));
    $tpl->set('s', 'HIDDENSESSION', $sess->hidden_session(true));
    $tpl->set('s', 'TMP_FIRSTEDIT', $tmp_firstedit);
    $tpl->set('s', 'IDART', $idart);
    $tpl->set('s', 'SID', $sess->id);
    $tpl->set('s', 'IDCAT', $idcat);
    $tpl->set('s', 'IDARTLANG', $tmp_idartlang);

    $hiddenfields = '<input type="hidden" name="idcat" value="' . $idcat . '">
                     <input type="hidden" name="idart" value="' . $idart . '">
                     <input type="hidden" name="send" value="1">';

    $tpl->set('s', 'HIDDENFIELDS', $hiddenfields);

    // Show path of selected category to user
    $catString = '';
    prCreateURLNameLocationString($idcat, ' > ', $catString, true, 'breadcrumb');

    $tpl->set('s', 'TITEL', i18n("Meta-Tags Verwaltung"));
    $tpl->set('s', 'CATEGORY', i18n("Sie sind hier") . ": " . $catString . ' ' . htmlspecialchars($tmp_title));

    // Title
    $tpl->set('s', 'TITEL', i18n("Title"));

    if (isset($tmp_notification)) {
        $tpl->set('s', 'NOTIFICATION', '<tr><td colspan="4">' . $tmp_notification . '<br></td></tr>');
    } else {
        $tpl->set('s', 'NOTIFICATION', '');
    }
    // Page title
    $title_input = '<input type="text" ' . $disabled . ' class="text_medium" name="page_title" style="width:400px;" value="' . htmlspecialchars($tmp_page_title) . '">';
    $tpl->set("s", "TITLE-INPUT", $title_input);

    if (($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
        $langscripts = '<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-' . $lang_short . '.js"></script>
        <script type="text/javascript" src="scripts/jquery/jquery.ui.datepicker-' . $lang_short . '.js"></script>';
        $tpl->set('s', 'CAL_LANG', $langscripts);
    } else {
        $tpl->set('s', 'CAL_LANG', '');
    }

    $tpl->set('s', 'PATH_TO_CALENDER_PIC', $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'calendar.gif');

    // Meta-Tags
    $availableTags = conGetAvailableMetaTagTypes();

    foreach ($availableTags as $key => $value) {
        $tpl->set('d', 'METAINPUT', 'META' . $value);

        switch ($value["fieldtype"]) {
            case "text":
                if ($value["name"] == 'date') {
                    $element = '<input ' . $disabled . ' class="text_medium" type="text" name="META' . $value["name"] . '" id="META' . $value["name"] . '" style="width:380px;" maxlength=' . $value["maxlength"] . ' value="' . htmlspecialchars(conGetMetaValue($tmp_idartlang, $key)) . '">';
                } else {
                    $element = '<input ' . $disabled . ' class="text_medium" type="text" name="META' . $value["name"] . '" id="META' . $value["name"] . '" style="width:400px;" maxlength=' . $value["maxlength"] . ' value="' . htmlspecialchars(conGetMetaValue($tmp_idartlang, $key)) . '">';
                }
                break;
            case "textarea":
                $element = '<textarea ' . $disabled . ' class="text_medium" name="META' . $value["name"] . '" id="META' . $value["name"] . '" style="width:400px;" rows=3>' . htmlspecialchars(conGetMetaValue($tmp_idartlang, $key)) . '</textarea>';
                break;
        }


        $tpl->set('d', 'METAFIELDTYPE', $element);
        //$tpl->set('d', 'METAVALUE', conGetMetaValue($tmp_idartlang,$key));
        $tpl->set('d', 'METATITLE', $value["name"] . ':');
        $tpl->next();
    }

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
    $tpl->set('s', 'SUMMARY-INPUT', '<textarea ' . $disabled . ' style="width:400px" class="text_medium" name="summary" cols="50" rows="5">' . $tmp_summary . '</textarea>');

    $sql = "SELECT
                b.idcat
            FROM
                " . $cfg["tab"]["cat"] . " AS a,
                " . $cfg["tab"]["cat_lang"] . " AS b,
                " . $cfg["tab"]["cat_art"] . " AS c
            WHERE
                a.idclient = " . cSecurity::toInteger($client) . " AND
                a.idcat    = b.idcat AND
                c.idcat    = b.idcat AND
                c.idart    = " . cSecurity::toInteger($idart);

    $db->query($sql);
    $db->next_record();

    $midcat = $db->f("idcat");

    if (isset($idart)) {
        if (!isset($idartlang) || 0 == $idartlang) {
            $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart=" . cSecurity::toInteger($idart) . " AND idlang=" . cSecurity::toInteger($lang);
            $db->query($sql);
            $db->next_record();
            $idartlang = $db->f("idartlang");
        }
    }

    if (isset($midcat)) {
        if (!isset($idcatlang) || 0 == $idcatlang) {
            $sql = "SELECT idcatlang FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat=" . cSecurity::toInteger($midcat) . " AND idlang=" . cSecurity::toInteger($lang);
            $db->query($sql);
            $db->next_record();
            $idcatlang = $db->f("idcatlang");
        }
    }

    if (isset($midcat) && isset($idart)) {
        if (!isset($idcatart) || 0 == $idcatart) {
            $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idart=" . cSecurity::toInteger($idart) . " AND idcat=" . cSecurity::toInteger($midcat);
            $db->query($sql);
            $db->next_record();
            $idcatart = $db->f("idcatart");
        }
    }

    if (0 != $idart && 0 != $midcat) {
        $script = 'artObj.setProperties("' . $idart . '", "' . $idartlang . '", "' . $midcat . '", "' . $idcatlang . '", "' . $idcatart . '", "' . $lang . '");';
    } else {
        $script = 'artObj.reset();';
    }

    $tpl->set('s', 'DATAPUSH', $script);

    $tpl->set('s', 'BUTTONDISABLE', $disabled);

    if ($inUse == true) {
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok_off.gif');
    } else {
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok.gif');
    }

    if ($tmp_usetimemgmt == '1') {
        if ($tmp_datestart == "0000-00-00 00:00:00" && $tmp_dateend == "0000-00-00 00:00:00") {
            $message = sprintf(i18n("Please fill in the start date and/or the end date!"));
            $notification->displayNotification("warning", $message);
        }
    }

    //add new meta in DB
    unset($tpl2);
    $result = array(
        "metatype" => "",
        "fieldtype" => "text",
        "maxlength" => "255",
        "fieldname" => "name"
    );
    $tpl2 = new Template();
    $tpl2->set('s', 'METATITLE', i18n("Neue Meta_Tag"));

    $sql = "SHOW FIELDS FROM `" . $cfg['tab']['meta_type'] . "`";

    $db->query($sql);

    while ($db->next_record()) {
        if ($db->f("Field") != 'idmetatype') {
            $tpl2->set('d', 'METATITLE', $db->f("Field"));
            $tpl2->set('d', 'METAFIELDTYPE', '<input type="text" onblur="restoreOnBlur(this, \'' . $result[$db->f("Field")] . '\')" onfocus="clearOnFocus(this, \'' . $result[$db->f("Field")] . '\');" value="' . $result[$db->f("Field")] . '" maxlength="255" style="width:400px;" id="META' . $db->f("Field") . '" name="META' . $db->f("Field") . '" class="text_medium"/>');
            $tpl2->next();
        }
    }

    $select = $tpl2->generate($cfg["path"]["templates"] . $cfg["templates"]["con_meta_addnew"], true);

    // accessible by the current user (sysadmin client admin) anymore.
    $aUserPerm = explode(',', $auth->auth['perm']);
    if (!in_array('sysadmin', $aUserPerm)) {
        $tpl->set('s', 'ADDMETABTN', '&nbsp;');
        $tpl->set('s', 'ADDNEWMETA', '&nbsp;');
    } else {
        $tpl->set('s', 'ADDMETABTN', '<span id="addMeta" style="color: green;">+</span>');
        $tpl->set('s', 'ADDNEWMETA', $select);
    }
    //breadcrumb onclick
    $tpl->set('s', 'iIdcat', $idcat);
    $tpl->set('s', 'iIdtpl', $idtpl);
    $tpl->set('s', 'SYNCOPTIONS', -1);
    $tpl->set('s', 'SESSION', $contenido);
    $tpl->set('s', 'DISPLAY_MENU', 1);

    // Genereate the Template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_meta_edit_form']);
} else {
    // User has no permission to see this form
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>