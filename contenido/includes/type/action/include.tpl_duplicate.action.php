<?php

/**
 * Backend action file tpl_duplicate
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if ($perm->have_perm_area_action($area, "tpl_duplicate") || $perm->have_perm_area_action_item($area, "tpl_duplicate", $idtpl)) {
    cInclude('includes', 'functions.tpl.php');
    $idtpl = tplDuplicateTemplate($idtpl);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>