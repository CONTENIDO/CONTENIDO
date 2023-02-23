<?php

/**
 * Backend action file lang_deletelanguage
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

cInclude('includes', 'functions.lang.php');

if ($perm->have_perm_area_action("lang_edit", "lang_deletelanguage")) {
    if (!is_numeric($targetclient)) {
        $targetclient = $client;
    }

    $errno = langDeleteLanguage($idlang, $targetclient);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
