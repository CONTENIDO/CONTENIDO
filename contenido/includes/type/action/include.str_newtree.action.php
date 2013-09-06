<?php
/**
 * Backend action file str_newtree
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

cInclude('includes', 'functions.str.php');

if ($perm->have_perm_area_action("str", "str_newtree")) {
    $tmp_newid = strNewTree($categoryname, $categoryalias, $visible, $public, $idtplcfg);
    strRemakeTreeTable();
    cApiCecHook::execute("Contenido.Action.str_newtree.AfterCall", array(
        'newcategoryid' => $tmp_newid,
        'categoryname'  => $categoryname,
        'categoryalias' => $categoryalias,
        'visible'       => $visible,
        'public'        => $public,
        'idtplcfg'      => $idtplcfg,
    ));
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>