<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Blank left top page
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2002-01-21
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.left_top_blank.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->reset();
$tpl->generate($cfg["path"]["templates"] . $cfg['templates']['left_top_blank']);
?>