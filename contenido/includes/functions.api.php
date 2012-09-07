<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido API loader
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-08-08
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id: functions.api.php 309 2008-06-26 10:06:56Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('includes', 'functions.api.category.php');
cInclude('includes', 'functions.api.string.php');
cInclude('includes', 'functions.api.images.php');
cInclude('classes',  'class.genericdb.php');
cInclude('classes',  'class.templateconfig.php');
cInclude('classes',  'class.template.php');
cInclude('classes',  'class.article.php');
cInclude('classes',  'class.search.php');
cInclude('classes',  'contenido/class.client.php');

?>
