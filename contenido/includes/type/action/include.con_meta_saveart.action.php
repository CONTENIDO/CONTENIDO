<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_meta_saveart action
 *
 * @package CONTENIDO Backend Includes
 * @version 0.0.1
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$artLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
$artLang->set('pagetitle', $_POST["page_title"]);
$artLang->store();

$availableTags = conGetAvailableMetaTagTypes();

foreach ($availableTags as $key => $value) {
    conSetMetaValue($idartlang, $key, $_POST["META" . $value["metatype"]]);
}

// meta tags have been saved, so clear the article cache
$purge = new cSystemPurge();
$purge->clearArticleCache($idartlang);

$notification->displayNotification("info", i18n("Changes saved"));
