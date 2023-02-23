<?php

/**
 * Backend action file mod_edit
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

/**
 * @var cPermission $perm
 * @var cGuiNotification $notification
 * @var string $area
 *
 * Form variables:
 * @var int $idmod
 * @var string $customtype
 * @var string $name
 * @var string $descr
 * @var string $input
 * @var string $output
 * @var string $template
 */

cInclude('includes', 'functions.mod.php');

if ($perm->have_perm_area_action($area, "mod_edit")) {
    if (empty($type)) {
        $type = $customtype;
    }

    $readOonly = (getEffectiveSetting("client", "readonly", "false") == "true");
    if($readOonly) {
        cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
    } else {
        // this is used to determine if the left bottom frame has to be reloaded
        $cApiModule = new cApiModule($idmod);
        $moduleNameChanged = $cApiModule->get('name') != stripslashes($name);

        $idmod = modEditModule($idmod, $name, $descr, $input, $output, $template ?? '', $type);
    }

} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
