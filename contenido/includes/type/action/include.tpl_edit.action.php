<?php

/**
 * Backend action file tpl_edit
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

global $perm, $area, $idtpl, $changelayout, $tplname, $description, $idlay, $c, $defaulttemplate, $notification;

if ($perm->have_perm_area_action($area, "tpl_edit") || $perm->have_perm_area_action_item($area, "tpl_edit", $idtpl)) {
    cInclude('includes', 'functions.tpl.php');
    $idtpl = tplEditTemplate($changelayout, $idtpl, $tplname, $description, $idlay, $c, $defaulttemplate);
    $idtpl = cSecurity::toInteger($idtpl);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
