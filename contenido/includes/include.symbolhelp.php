<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Display the symbol help
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-14
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.symbolhelp.php 369 2008-06-27 14:26:40Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['symbolhelp']);
?>