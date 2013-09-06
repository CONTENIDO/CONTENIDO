<?php
/**
 * Backend action file mod_edit
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

cInclude('includes', 'functions.mod.php');

if ($perm->have_perm_area_action("mod", "mod_edit")) {
    if (empty($type)) {
        $type = $customtype;
    }

    $idmod = modEditModule($idmod, $name, $descr, $input, $output, $template, $type);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
