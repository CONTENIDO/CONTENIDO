<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Layout Preview
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.lay_preview.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$sql = "SELECT code FROM ".$cfg["tab"]["lay"]." WHERE idlay='".Contenido_Security::toInteger($_GET['idlay'])."'";
$db->query($sql);

if (!$db->next_record()) {
	echo i18n("No such layout");
} else {

	$code = $db->f("code");

	/* Insert base href */
	$base = '<base href="'.$cfgClient[$client]["path"]["htmlpath"].'">';
	$tags = $base;

	$code = str_replace("<head>", "<head>\n".$tags, $code);

	eval("?>\n".Contenido_Security::unescapeDB($code)."\n<?php\n");
}
?>