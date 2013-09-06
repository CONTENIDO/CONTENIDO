<?php
/**
 * Backend action file tpl_delete
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

if ($perm->have_perm_area_action("tpl", "tpl_delete") || $perm->have_perm_area_action_item("tpl", "tpl_delete", $idtpl)) {
    cInclude('includes', 'functions.tpl.php');
    $tmp_notification =  tplDeleteTemplate($idtpl);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>