<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Config file for Newsletter plugin
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Plugins
 * @subpackage Newsletter
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// plugin includes
plugin_include('newsletter', 'classes/class.newsletter.php');
plugin_include('newsletter', 'classes/class.newsletter.logs.php');
plugin_include('newsletter', 'classes/class.newsletter.jobs.php');
plugin_include('newsletter', 'classes/class.newsletter.groups.php');
plugin_include('newsletter', 'classes/class.newsletter.recipients.php');
?>