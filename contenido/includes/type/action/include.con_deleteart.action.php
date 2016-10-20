<?php

/**
 * Backend action file con_deleteart
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

cInclude('includes', 'functions.con.php');

if ($perm->have_perm_area_action("con", "con_deleteart") || $perm->have_perm_area_action_item("con", "con_deleteart", $idcat)) {

    if (isset($_POST['idarts'])) {
        //delete articles (bulk editing)
        $idarts = json_decode($_POST['idarts'], true);
        foreach ($idarts as $article) {
            conDeleteArt($article);
        }
    } else  {
        conDeleteArt($idart);
    }

    $pim = new PimPlugin();
    $available = $pim->isPluginAvailable('URL Shortener');

    if ($available === true) {

        $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Action.con_deleteart.AfterCall');
        if ($_cecIterator->count() > 0) {

            if (isset($_POST['idarts'])) {
                $idarts = json_decode($_POST['idarts'], true);
                while (false !== $chainEntry = $_cecIterator->next()) {
                    foreach ($idarts as $article) {
                        $chainEntry->execute($article);
                    }
                }
            } else {
                while (false !== $chainEntry = $_cecIterator->next()) {
                    $chainEntry->execute($idart);
                }
            }
        }
    }

    $tmp_notification = $notification->returnNotification("ok", i18n("Article deleted"));
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

?>