<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Configuration file for HTTP parameter check feature.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.3
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2008-02-08
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/* Do not edit or add anything in check array here, use config.http_check.local.php for custom entries. Otherwise
 * your changes could be damaged on next update!
 *
 * The syntax is very simple. The array $aCheck contains as first key the parameter type - i.e. GET or POST.
 * The second key is the parameter's name (e.g. idart). The value is one of the following constants:
 *
 * CON_CHECK_INTEGER           => integer value
 * CON_CHECK_PRIMITIVESTRING   => simple string
 * CON_CHECK_STRING            => more complex string
 * CON_CHECK_HASH32            => 32-character hash
 * CON_CHECK_BELANG			   => Valid values for belang
 * CON_CHECK_AREASTRING		   => Checks for a string consisting of letters and "_" only
 * CON_CHECK_PATHSTRING		   => Validates file paths for file uploading (matches "folder/", "", "dbfs:" and "dbfs:/*")
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

/*
 * Mode
 *
 * Currently the modes 'stop' and 'continue' are supported. In "continue" mode violations will be logged only.
 * Enabling "stop" mode will force CONTENIDO to stop on any violation.
 */
$sMode = 'stop';

/*
 * The parameters listed here will be checked against the specified pattern.
 * Unkown parameters (aka they are not listed here) will be considered to be fine.
 */
#### Check whitelist ####
$aCheck = array();
$aCheck['GET']['idart'] = CON_CHECK_INTEGER;
$aCheck['GET']['idcat'] = CON_CHECK_INTEGER;
$aCheck['GET']['idartlang'] = CON_CHECK_INTEGER;
$aCheck['GET']['idcatart'] = CON_CHECK_INTEGER;
$aCheck['GET']['lang'] = CON_CHECK_INTEGER;
$aCheck['GET']['changelang'] = CON_CHECK_INTEGER;
$aCheck['GET']['idcatlang'] = CON_CHECK_INTEGER;

$aCheck['GET']['client'] = CON_CHECK_INTEGER;
$aCheck['GET']['area'] = CON_CHECK_AREASTRING;
$aCheck['GET']['frame'] = CON_CHECK_INTEGER;

$aCheck['GET']['tmpchangelang'] = CON_CHECK_INTEGER;
$aCheck['GET']['changeview'] = CON_CHECK_PRIMITIVESTRING;
$aCheck['GET']['action'] = CON_CHECK_PRIMITIVESTRING;
$aCheck['GET']['changeclient'] = CON_CHECK_INTEGER;

$aCheck['GET']['page'] = CON_CHECK_INTEGER;

$aCheck['GET']['catname'] = CON_CHECK_PRIMITIVESTRING;
$aCheck['GET']['contenido'] = CON_CHECK_HASH32;

$aCheck['GET']['belang'] = CON_CHECK_BELANG;
$aCheck['GET']['path'] = CON_CHECK_PATHSTRING;


$aCheck['POST']['idart'] = CON_CHECK_INTEGER;

$aCheck['POST']['idcat'] = CON_CHECK_INTEGER;

$aCheck['POST']['idartlang'] = CON_CHECK_INTEGER;

$aCheck['POST']['idcatart'] = CON_CHECK_INTEGER;

$aCheck['POST']['lang'] = CON_CHECK_INTEGER;

$aCheck['POST']['changelang'] = CON_CHECK_INTEGER;

$aCheck['POST']['idcatlang'] = CON_CHECK_INTEGER;



$aCheck['POST']['client'] = CON_CHECK_INTEGER;

$aCheck['POST']['area'] = CON_CHECK_AREASTRING;

$aCheck['POST']['frame'] = CON_CHECK_INTEGER;



$aCheck['POST']['tmpchangelang'] = CON_CHECK_INTEGER;

$aCheck['POST']['changeview'] = CON_CHECK_PRIMITIVESTRING;

$aCheck['POST']['action'] = CON_CHECK_PRIMITIVESTRING;

$aCheck['POST']['changeclient'] = CON_CHECK_INTEGER;



$aCheck['POST']['page'] = CON_CHECK_INTEGER;



$aCheck['POST']['catname'] = CON_CHECK_PRIMITIVESTRING;

$aCheck['POST']['contenido'] = CON_CHECK_HASH32;



$aCheck['POST']['belang'] = CON_CHECK_BELANG;

$aCheck['POST']['path'] = CON_CHECK_PATHSTRING;

/*
 * If one of these parameters is set (either get or post) the script will halt.
 */
#### Paramater blacklist ####
$aBlacklist = array('cfg', 'cfgClient', 'contenido_path', '_PHPLIB', 'db', 'sess');
?>