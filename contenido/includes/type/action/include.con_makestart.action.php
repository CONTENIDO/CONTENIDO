<?php

/**
 * Backend action file con_makestart
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

cInclude('includes', 'functions.con.php');

if ($perm->have_perm_area_action("con", "con_makestart") || $perm->have_perm_area_action_item("con", "con_makestart", $idcat)) {
    conMakeStart($idcatart, $is_start);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
