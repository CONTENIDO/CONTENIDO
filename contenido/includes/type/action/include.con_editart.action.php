<?php
/**
 * Backend action file con_editart
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

$path = cRegistry::getBackendUrl() . "external/backendedit/";

if ($perm->have_perm_area_action("con", "con_editart") || $perm->have_perm_area_action_item("con", "con_editart", $idcat)) {
    if ($tmpchangelang != $lang) {
        $url = $sess->url("front_content.php?changeview=$changeview&client=$client&lang=$lang&action=$action&idartlang=$idartlang&idart=$idart&idcat=$idcat&tmpchangelang=$tmpchangelang");
    } else {
        $url = $sess->url("front_content.php?changeview=$changeview&client=$client&lang=$lang&action=$action&idartlang=$idartlang&idart=$idart&idcat=$idcat&lang=$lang");
    }

    header("location: $path$url");
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}

