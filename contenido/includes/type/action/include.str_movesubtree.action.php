<?php

/**
 * Backend action file str_movesubtree
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

cInclude('includes', 'functions.str.php');

if ($perm->have_perm_area_action("str", "str_movesubtree") || $perm->have_perm_area_action_item("str", "str_movesubtree", $idcat)) {
    strMoveSubtree($idcat, $parentid_new, $preid_new, $postid_new);
    strRemakeTreeTable();
    cApiCecHook::execute("Contenido.Action.str_movesubtree.AfterCall", array(
        'idcat'        => $idcat,
        'parentid_new' => $parentid_new
    ));
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>