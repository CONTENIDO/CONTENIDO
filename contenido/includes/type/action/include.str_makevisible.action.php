<?php

/**
 * Backend action file str_makevisible
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

cInclude('includes', 'functions.str.php');

if ($perm->have_perm_area_action("str", "str_makevisible") || $perm->have_perm_area_action_item("str", "str_makevisible", $idcat)) {
    //CON-1756 offline/online toggle should not toggle offline/online tag for sub categories.
    conMakeCatOnline($idcat, $lang, !$visible);
    // strMakeVisible($idcat, $lang, !$visible);
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
