<?php

/**
 * Backend action file str_renamecat
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

cInclude('includes', 'functions.str.php');

if ($perm->have_perm_area_action("str", "str_renamecat") || $perm->have_perm_area_action_item("str", "str_renamecat", $idcat)) {
    strRenameCategory($idcat, $lang, $newcategoryname, $newcategoryalias);
    cApiCecHook::execute(
        "Contenido.Action.str_renamecat.AfterCall",
        [
            'idcat'            => $idcat,
            'lang'             => $lang,
            'newcategoryname'  => $newcategoryname,
            'newcategoryalias' => $newcategoryalias,
        ]
    );
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>