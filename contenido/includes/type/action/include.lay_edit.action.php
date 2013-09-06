<?php
/**
 * Backend action file lay_edit
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

cInclude('includes', 'functions.lay.php');

if ($perm->have_perm_area_action("lay", "lay_edit")) {
    $idlay = layEditLayout($idlay, $layname, $description, $code);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>