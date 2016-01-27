<?php

/**
 * Backend action file con_makepublic
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

cInclude('includes', 'functions.con.php');

if ($perm->have_perm_area_action("con", "con_makepublic") || $perm->have_perm_area_action_item("con", "con_makepublic", $idcat)) {
    conMakePublic($idcat, $lang, $public);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>