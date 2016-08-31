<?php

/**
 * Backend action file con_content
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

if ($perm->have_perm_area_action($area, "con_content") || $perm->have_perm_area_action_item($area, "con_content", $idcat)) {
    cInclude("includes", "functions.tpl.php");
    include(cRegistry::getBackendPath() . $cfg["path"]["includes"] . "include.con_content_list.php");
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>