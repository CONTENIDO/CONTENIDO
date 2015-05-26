<?php

/**
 * Backend action file con_meta_saveart
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con2.php');

if ($perm->have_perm_area_action($area, "con_meta_edit") || $perm->have_perm_area_action_item($area, "con_meta_edit", $idcat)) {
    $oldData = array();

    $availableTags = conGetAvailableMetaTagTypes();
    foreach ($availableTags as $key => $value) {
        $oldData[$value['metatype']] = conGetMetaValue($idartlang, $key);
    }

    $artLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
    $artLang->set('pagetitle', $_POST['page_title']);
    $artLang->set('urlname', $_POST['alias']);
    $artLang->set('sitemapprio', $_POST['sitemap_prio']);
    $artLang->set('changefreq', $_POST['sitemap_change_freq']);
    $artLang->store();

    $robots = '';
    $robotArray = ($_POST['robots'] == NULL) ? array() : $_POST['robots'];
    if (in_array('noindex', $robotArray)) {
        $robots .= 'noindex, ';
    } else {
        $robots .= 'index, ';
    }
    if (in_array('nosnippet', $robotArray)) {
        $robots .= 'nosnippet, ';
    }
    if (in_array('noimageindex', $robotArray)) {
        $robots .= 'noimageindex, ';
    }
    if (in_array('noarchive', $robotArray)) {
        $robots .= 'noarchive, ';
    }

    if (in_array('nofollow', $robotArray)) {
        $robots .= 'nofollow';
    } else {
        $robots .= 'follow';
    }

    $newData = array();

    $versioning = new cContentVersioning();
    $version = NULL;
    if ($versioning->getState() != 'disabled') {
        // safe original version
        if ($versioning->getState() == 'simple') {
            $where = 'idartlang = ' . $idartlang;
            $metaTagVersionColl = new cApiMetaTagVersionCollection();
            $metaTagVersionIds = $metaTagVersionColl->getIdsByWhereClause($where);
            if (empty($metaTagVersionIds)) {
                $artLangVersion = $versioning->createArticleLanguageVersion($artLang->toArray());
                $artLangVersion->markAsCurrentVersion(1);
                $version = $artLangVersion->get('version');
            }
        }
        // create article version
        $artLangVersion = $versioning->createArticleLanguageVersion($artLang->toArray());
        $artLangVersion->markAsCurrentVersion(1);
        $version = $artLangVersion->get('version');
    }

    foreach ($availableTags as $key => $value) {
        if ($value['metatype'] == 'robots') {
            conSetMetaValue($idartlang, $key, $robots);//, $version);
            $newData[$value['metatype']] = $robots;
        } elseif ($value["metatype"] == "date" || $value["metatype"] == "expires") {
            $atime = '';
            $dateValue = $_POST['META' . $value['metatype']];
            // fix store hours and minutes
                // if (is_int(strtotime($dateValue))) {
                // $atime = date('c', strtotime($dateValue));
                // }
            $atime = $dateValue;
            conSetMetaValue($idartlang, $key, $atime, $version);
            $newData[$value['metatype']] = $atime;
        } else {
            conSetMetaValue($idartlang, $key, $_POST['META' . $value['metatype']], $version);
            $newData[$value['metatype']] = $_POST['META' . $value['metatype']];
        }
    }

    // meta tags have been saved, so clear the article cache
    $purge = new cSystemPurge();
    $purge->clearArticleCache($idartlang);

    // Add a new Meta Tag in DB
    $validMeta = true;
    if (!empty($METAmetatype) && preg_match('/^([a-zA-Z])([a-zA-Z0-9\.\:\-\_]*$)/', $METAmetatype)) {
        $sql = "INSERT INTO `" . $cfg['tab']['meta_type'] . "` (
                    `metatype` ,
                    `fieldtype` ,
                    `maxlength` ,
                    `fieldname`
                )
                VALUES (
                    '" . $METAmetatype . "', '" . $METAfieldtype . "', '" . $METAmaxlength . "', '" . $METAfieldname . "'
                );";
        $db->query($sql);
    } else if (!empty($METAmetatype)) {
        $validMeta = false;
    }

    cApiCecHook::execute('Contenido.Action.con_meta_saveart.AfterCall', $idart, $newData, $oldData);

    if ($validMeta) {
        $notification->displayNotification('info', i18n('Changes saved'));
    } else {
        $notification->displayNotification("error", i18n("Attribute content not valid; attend information button"));
    }
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
