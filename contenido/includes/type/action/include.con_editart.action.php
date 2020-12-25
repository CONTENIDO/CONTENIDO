<?php

/**
 * Backend action file con_editart
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

global $sess, $tmpchangelang, $changeview, $client, $lang, $action, $idartlang, $idart, $idcat, $lang;

$path = cRegistry::getBackendUrl() . "external/backendedit/";

if (isset($tmpchangelang) && $tmpchangelang != $lang) {
    $url = $sess->url("front_content.php?changeview=$changeview&client=$client&lang=$lang&action=$action&idartlang=$idartlang&idart=$idart&idcat=$idcat&tmpchangelang=$tmpchangelang");
} else {
    $url = $sess->url("front_content.php?changeview=$changeview&client=$client&lang=$lang&action=$action&idartlang=$idartlang&idart=$idart&idcat=$idcat&lang=$lang");
}

header("location: $path$url");
