<?php

/**
 * This file contains the backend page for previewing a layout.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$layoutInFile = new cLayoutHandler(cSecurity::toInteger($_GET['idlay']), '', $cfg, $lang);
if (($code = $layoutInFile->getLayoutCode()) == false) {
    echo i18n("No such layout");
}

// Insert base href
$base = '<base href="' . cRegistry::getFrontendUrl() . '">';
$tags = $base;

$code = str_replace("<head>", "<head>\n".$tags, $code);

eval("?>\n".cSecurity::unescapeDB($code)."\n<?php\n");

?>