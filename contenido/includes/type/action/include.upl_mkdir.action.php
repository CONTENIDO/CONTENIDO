<?php

/**
 * Backend action file upl_mkdir
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

if ($perm->have_perm_area_action("upl", "upl_mkdir") || $perm->have_perm_area_action_item("upl", "upl_mkdir", $idtpl)) {
    cInclude('includes', 'functions.upl.php');
    $errno = uplmkdir($path, $foldername);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>