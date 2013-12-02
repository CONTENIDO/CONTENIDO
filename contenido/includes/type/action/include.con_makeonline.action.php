<?php
/**
 * Backend action file con_makeonline
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

cInclude('includes', 'functions.con.php');

if ($perm->have_perm_area_action("con", "con_makeonline") || $perm->have_perm_area_action_item("con", "con_makeonline", $idcat)) {
    if (isset($_POST['idarts'])) {
        $idarts = json_decode($_POST['idarts'], true);
        $online = $_POST['invert'] == 1 ? 0 : 1;
        conMakeOnlineBulkEditing($idarts, $lang, $online);
    } else {
        conMakeOnline($idart, $lang);
    }
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>