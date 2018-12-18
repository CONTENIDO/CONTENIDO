<?php

/**
 * Backend action file lay_delete
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

cInclude('includes', 'functions.lay.php');

if ($perm->have_perm_area_action("lay", "lay_delete")) {
    $errno = layDeleteLayout($idlay);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
