<?php
/**
 * Configuration file for HTTP parameter check feature.
 * 
 * Do not edit or add anything in check array here, use config.http_check.local.php for custom entries. Otherwise
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
 * @version $Revision: 1.1 $
 * @author $Author: holger.librenz $
 * @copyright four for business AG <www.4fb.de>
 * 
 * $Id: config.http_check.php,v 1.1 2008/02/08 18:36:09 holger.librenz Exp $
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
$aCheck['GET']['lang'] = CON_CHECK_INTEGER;
$aCheck['GET']['changelang'] = CON_CHECK_INTEGER;

$aCheck['GET']['page'] = CON_CHECK_INTEGER;

$aCheck['GET']['catname'] = CON_CHECK_PRIMITIVESTRING;
$aCheck['GET']['contenido'] = CON_CHECK_HASH32;

?>