<?php

/**
 * Backend action file lang_activatelanguage
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if ($perm->have_perm_area_action("lang", "lang_activatelanguage") || $perm->have_perm_area_action_item("lang", "lang_activatelanguage", $idlang)) {
    cInclude('includes', 'functions.lang.php');
    langActivateDeactivateLanguage($idlang, 1);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>