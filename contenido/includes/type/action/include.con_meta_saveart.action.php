<?php
/**
 * Backend action file con_meta_saveart
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con2.php');

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
