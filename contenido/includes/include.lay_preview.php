<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Layout Preview
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2011-06-22, Rusmir Jusufovic, load layout from file
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


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