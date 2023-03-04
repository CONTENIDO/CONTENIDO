<?php

/**
 * Backend action file lay_edit
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

/**
 * @var cPermission $perm
 * @var cGuiNotification $notification
 * @var string $area
 * @var int $idlay
 */

cInclude('includes', 'functions.lay.php');

if ($perm->have_perm_area_action($area, "lay_edit")) {

    // check the read only setting and only apply changes if it's off
    $readOnly = (getEffectiveSetting('client', 'readonly', 'false') === 'true');
    if (!$readOnly) {
        $idlay = layEditLayout($idlay, $_POST['layname'], $_POST['description'], $_POST['code']);
    }
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
