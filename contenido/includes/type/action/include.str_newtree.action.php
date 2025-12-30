<?php

/**
 * Backend action file str_newtree
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
 * @var cGuiNotification $notification
 * @var string $categoryname
 * @var string $categoryalias
 * @var int $visible
 * @var int $public
 * @var int $idtplcfg
 */

cInclude('includes', 'functions.str.php');

$perm = cRegistry::getPerm();

if ($perm->have_perm_area_action('str', 'str_newtree')) {
    $tmp_newid = strNewTree($categoryname, $categoryalias, $visible, $public, $idtplcfg);
    strRemakeTreeTable();
    cApiCecHook::execute(
        'Contenido.Action.str_newtree.AfterCall',
        [
            'newcategoryid' => $tmp_newid,
            'categoryname' => $categoryname,
            'categoryalias' => $categoryalias,
            'visible' => $visible,
            'public' => $public,
            'idtplcfg' => $idtplcfg,
        ]
    );
} else {
    $notification->displayNotification('error', i18n("Permission denied"));
}
