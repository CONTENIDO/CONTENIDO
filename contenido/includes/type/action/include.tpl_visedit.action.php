<?php

/**
 * Backend action file tpl_visedit
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

if ($perm->have_perm_area_action($area, "tpl_visedit") || $perm->have_perm_area_action_item($area, "tpl_visedit", $idtpl)) {
    cInclude('includes', 'functions.tpl.php');
    $idtpl = tplEditTemplate($changelayout, $idtpl, $tplname, $description, $idlay, $c, $tplisdefault);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>