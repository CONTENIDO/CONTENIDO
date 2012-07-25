<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_changetemplate action
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if ($perm->have_perm_area_action("con", "con_changetemplate") || $perm->have_perm_area_action_item("con", "con_changetemplate", $idcat)) {
    conChangeTemplateForCat($idcat, $idtpl);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
?>