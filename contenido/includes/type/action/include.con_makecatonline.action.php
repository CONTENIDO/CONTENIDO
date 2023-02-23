<?php

/**
 * Backend action file con_makecatonline
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

if ($perm->have_perm_area_action("con", "con_makecatonline") || $perm->have_perm_area_action_item("con", "con_makecatonline", $idcat)) {
    conMakeCatOnline($idcat, $lang, $online);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
