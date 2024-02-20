<?php

/**
 * Backend action file con_meta_saveart
 *
 * @package    Core
 * @subpackage Backend
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cDb $db
 * @var string $area
 * @var int $idart
 * @var int $idcat
 * @var int $idartlang
 * @var cGuiNotification $notification
 *
 * // Meta tags form values
 * @var string $METAmetatype
 * @var string $METAfieldtype
 * @var string $METAmaxlength
 * @var string $METAfieldname
 */

cInclude('includes', 'functions.con2.php');

if ($perm->have_perm_area_action($area, 'con_meta_edit') || $perm->have_perm_area_action_item($area, 'con_meta_edit', $idcat)) {
    $oldData = [];

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

    $robots = [];
    $requestRobots = isset($_POST['robots']) && is_array($_POST['robots']) ? $_POST['robots'] : [];
    $robotsValues = [
        ['noindex', 'index'],
        'nosnippet',
        'noimageindex',
        'noarchive',
        'noodp',
        ['nofollow', 'follow']
    ];
    foreach ($robotsValues as $robotsValue) {
        if (is_array($robotsValue)) {
            $robots[] = in_array($robotsValue[0], $requestRobots) ? $robotsValue[0] : $robotsValue[1];
        } elseif (in_array($robotsValue, $requestRobots)) {
            $robots[] = $robotsValue;
        }
    }
    $robots = implode(', ', $robots);

    $newData = [];

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
        } elseif ($value['metatype'] == 'date' || $value['metatype'] == 'expires') {
            $atime = '';
            $dateValue = $_POST['META' . $value['metatype']] ?? '';
            // fix store hours and minutes
            // if (is_int(strtotime($dateValue))) {
            // $atime = date('c', strtotime($dateValue));
            // }
            $atime = $dateValue;
            conSetMetaValue($idartlang, $key, $atime, $version);
            $newData[$value['metatype']] = $atime;
        } else {
            $contentMetaValue = $_POST['META' . $value['metatype']] ?? '';
            $contentMetaValue = str_replace('"', '', $contentMetaValue);

            conSetMetaValue($idartlang, $key, $contentMetaValue, $version);
            $newData[$value['metatype']] = $contentMetaValue;
        }
    }

    // meta tags have been saved, so clear the article cache
    $purge = new cSystemPurge();
    $purge->clearArticleCache($idartlang);

    // Add a new Meta Tag in DB
    $validMeta = true;
    if (!empty($METAmetatype) && preg_match('/^([a-zA-Z])([a-zA-Z0-9\.\:\-\_]*$)/', $METAmetatype)) {
        $metaTypeColl = new cApiMetaTypeCollection();
        $metaTypeColl->create($METAmetatype, $METAfieldtype, $METAmaxlength, $METAfieldname);
    } elseif (!empty($METAmetatype)) {
        $validMeta = false;
    }

    cApiCecHook::execute('Contenido.Action.con_meta_saveart.AfterCall', $idart, $newData, $oldData);

    if ($validMeta) {
        $notification->displayNotification('ok', i18n('Changes saved'));
    } else {
        $notification->displayNotification('error', i18n("Attribute content not valid; attend information button"));
    }
} else {
    $notification->displayNotification('error', i18n("Permission denied"));
}
