<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * TINYMCE 1.45rc1 PHP WYSIWYG editor config
 * Main editor configuration file for CONTENIDO
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.0.3
 * @author     Martin Horwath, horwath@dayside.net
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2005-06-10
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

// include CONTENIDO config file
$contenido_path = implode (DIRECTORY_SEPARATOR , array_slice(explode(DIRECTORY_SEPARATOR , dirname(__FILE__)), 0, -3)) . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR;

if (file_exists( $contenido_path . 'startup.php'))
{
	@include_once ($contenido_path . 'startup.php');
} else {
	@include_once ($contenido_path . 'config.php');
}

cInclude ("includes", 'functions.con.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.i18n.php');
cInclude ("includes", 'functions.api.php');

$db = new DB_Contenido;

if ($cfgClient["set"] != "set") // CONTENIDO
{
	rereadClients();
}


?>
