<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Configuration file for HTTP parameter check feature.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.2
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2008-02-08
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *	 modified 2008-09-09, Dominik Ziegler, removed whitespace at beginning of file
 *	 modified 2008-09-10, Ingo van Peeren, added 'changeview', 'action' and 'tmpchangelang' for
 *	                                       backend editing and preview 
 *	 modified 2008-09-17, Dominik Ziegler, added 'client' for backend editing and preview
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/* Do not edit or add anything in check array here, use config.http_check.local.php for custom entries. Otherwise
 * your changes could be damaged on next update!
 * 
 * The syntax is very simple. The array $aCheck contains as first key the parameter type - i.e. GET or POST.
 * The second key is the parameter's name (e.g. idart). The value is one of the following constants:
 * 
 * 	CON_CHECK_INTEGER			=> integer value
 *	CON_CHECK_PRIMITIVESTRING	=> simple string
 *	CON_CHECK_STRING			=> more complex string
 *	CON_CHECK_HASH32			=> 32-character hash
 *
 */

#### Logging and mode ####
/*
 * Logging
 *
 * To disable logging, set this value to false. But keep in mind
 * that logging has to be enabled if you want training mode in action.
 */
$bLog = true;
$sLogPath = 'logs/hacktrials.log';

/*
 * Mode
 * 
 * Currently the modes 'training' and 'arcade' are supported. In training mode violation will be logged only.
 * Enabling arcade mode will force contenido to stop on every violations.
 */
$sMode = 'training';


#### Check whitelist ####
$aCheck = array();
$aCheck['GET']['idart'] = CON_CHECK_INTEGER;
$aCheck['GET']['idcat'] = CON_CHECK_INTEGER;
$aCheck['GET']['idartlang'] = CON_CHECK_INTEGER;
$aCheck['GET']['idcatart'] = CON_CHECK_INTEGER;
$aCheck['GET']['lang'] = CON_CHECK_INTEGER;
$aCheck['GET']['changelang'] = CON_CHECK_INTEGER;

$aCheck['GET']['client'] = CON_CHECK_INTEGER;

$aCheck['GET']['tmpchangelang'] = CON_CHECK_INTEGER;
$aCheck['GET']['changeview'] = CON_CHECK_PRIMITIVESTRING;
$aCheck['GET']['action'] = CON_CHECK_PRIMITIVESTRING;

$aCheck['GET']['page'] = CON_CHECK_INTEGER;

$aCheck['GET']['catname'] = CON_CHECK_PRIMITIVESTRING;
$aCheck['GET']['contenido'] = CON_CHECK_HASH32;

?>