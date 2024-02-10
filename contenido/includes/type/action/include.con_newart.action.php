<?php

/**
 * Backend action file con_newart
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

if ($perm->have_perm_area_action($area, "con_newart") || $perm->have_perm_area_action_item($area, "con_newart", $idcat)) {
    // Code for action 'con_newart'
    $sql = "SELECT
                a.idtplcfg,
                a.name
            FROM
                " . $cfg["tab"]["cat_lang"] . " AS a,
                " . $cfg["tab"]["cat"] . " AS b
            WHERE
                a.idlang    = '" . $lang . "' AND
                b.idclient  = '" . $client . "' AND
                a.idcat     = '" . $idcat . "' AND
                b.idcat     = a.idcat";

    $db->query($sql);
    $db->nextRecord();

    if ($db->f("idtplcfg") != 0) {
        $newart = true;
    } else {
        $page = new cGuiPage("con_newart");
        $page->displayCriticalError(i18n("This category has no templates assigned."));
        $page->render();
    }
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
