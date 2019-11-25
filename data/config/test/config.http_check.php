<?php
/**
 * This file contains the configuration for the HTTP parameter check feature.
 *
 * @package          Core
 * @subpackage       Backend_ConfigFile
 * @version          SVN Revision $Rev:$
 *
 * @author           Holger Librenz
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/* Do not edit or add anything in check array here, use config.http_check.local.php for custom entries. Otherwise
 * your changes could be damaged on next update!
 *
 * The syntax is very simple. The array $aCheck contains as first key the parameter type - i.e. GET or POST.
 * The second key is the parameter's name (e.g. idart). The value is one of the following constants:
 *
 * cRequestValidator::CHECK_INTEGER           => integer value
 * cRequestValidator::CHECK_PRIMITIVESTRING   => simple string
 * cRequestValidator::CHECK_STRING            => more complex string
 * cRequestValidator::CHECK_HASH32            => 32-character hash
 * cRequestValidator::CHECK_BELANG            => Valid values for belang
 * cRequestValidator::CHECK_AREASTRING        => Checks for a string consisting of letters and "_" only
 * cRequestValidator::CHECK_PATHSTRING        => Validates file paths for file uploading (matches "folder/", "", "dbfs:" and "dbfs:/*")
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
$aCheck['GET']['idart'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['idcat'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['idartlang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['idcatart'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['lang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['changelang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['idcatlang'] = cRequestValidator::CHECK_INTEGER;

$aCheck['GET']['client'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['area'] = cRequestValidator::CHECK_AREASTRING;
$aCheck['GET']['frame'] = cRequestValidator::CHECK_INTEGER;

$aCheck['GET']['tmpchangelang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['GET']['changeview'] = cRequestValidator::CHECK_PRIMITIVESTRING;
$aCheck['GET']['action'] = cRequestValidator::CHECK_PRIMITIVESTRING;
$aCheck['GET']['changeclient'] = cRequestValidator::CHECK_INTEGER;
//$aCheck['GET']['contenido'] = cRequestValidator::CHECK_HASH32;

$aCheck['GET']['page'] = cRequestValidator::CHECK_INTEGER;

$aCheck['GET']['catname'] = cRequestValidator::CHECK_PRIMITIVESTRING;

$aCheck['GET']['belang'] = cRequestValidator::CHECK_BELANG;
$aCheck['GET']['path'] = cRequestValidator::CHECK_PATHSTRING;


$aCheck['POST']['idart'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['idcat'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['idartlang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['idcatart'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['lang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['changelang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['idcatlang'] = cRequestValidator::CHECK_INTEGER;

$aCheck['POST']['client'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['area'] = cRequestValidator::CHECK_AREASTRING;
$aCheck['POST']['frame'] = cRequestValidator::CHECK_INTEGER;

$aCheck['POST']['tmpchangelang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['changeview'] = cRequestValidator::CHECK_PRIMITIVESTRING;
$aCheck['POST']['action'] = cRequestValidator::CHECK_PRIMITIVESTRING;
$aCheck['POST']['changeclient'] = cRequestValidator::CHECK_INTEGER;
$aCheck['POST']['page'] = cRequestValidator::CHECK_INTEGER;

$aCheck['POST']['catname'] = cRequestValidator::CHECK_PRIMITIVESTRING;
//$aCheck['POST']['contenido'] = cRequestValidator::CHECK_HASH32;

$aCheck['POST']['belang'] = cRequestValidator::CHECK_BELANG;
$aCheck['POST']['path'] = cRequestValidator::CHECK_PATHSTRING;

$aCheck['COOKIE']['idart'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['idcat'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['idartlang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['idcatart'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['lang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['changelang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['idcatlang'] = cRequestValidator::CHECK_INTEGER;

$aCheck['COOKIE']['client'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['area'] = cRequestValidator::CHECK_AREASTRING;
$aCheck['COOKIE']['frame'] = cRequestValidator::CHECK_INTEGER;

$aCheck['COOKIE']['tmpchangelang'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['changeview'] = cRequestValidator::CHECK_PRIMITIVESTRING;
$aCheck['COOKIE']['action'] = cRequestValidator::CHECK_PRIMITIVESTRING;
$aCheck['COOKIE']['changeclient'] = cRequestValidator::CHECK_INTEGER;
$aCheck['COOKIE']['page'] = cRequestValidator::CHECK_INTEGER;

$aCheck['COOKIE']['catname'] = cRequestValidator::CHECK_PRIMITIVESTRING;
//$aCheck['COOKIE']['contenido'] = cRequestValidator::CHECK_HASH32;

$aCheck['COOKIE']['belang'] = cRequestValidator::CHECK_BELANG;
$aCheck['COOKIE']['path'] = cRequestValidator::CHECK_PATHSTRING;

// If one of these parameters is set (either get or post) the script will halt.
#### Paramater blacklist ####
$aBlacklist = array('cfg', 'cfgClient', 'contenido_path', '_PHPLIB', 'db', 'sess');
?>