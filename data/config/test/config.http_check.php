<?php

/**
 * This file contains the configuration for the HTTP parameter check feature implemented in cRequestValidator.
 *
 * Do not edit or add anything in check array here, use config.http_check.local.php for custom entries.
 * Otherwise, your changes could be damaged on next update!
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
 * @package    Core
 * @subpackage Backend_ConfigFile
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Logging: To disable logging, set this value to false.
// But keep in mind that logging has to be enabled if you want training mode in action.
$bLog = true;

// Mode: Currently the modes 'stop' and 'continue' are supported.
// In "continue" mode violations will be logged only.
// Enabling "stop" mode will force CONTENIDO to stop on any violation.
$sMode = 'stop';

// Paramater blacklist: If one of these parameters is set (either get or post) the script will halt.
$aBlacklist = [
    'cfg',
    'cfgClient',
    'contenido_path',
    '_PHPLIB',
    'db',
    'sess',
];

// Paramater whitelist: The parameters listed here will be checked against the specified pattern.
// Unkown parameters (aka they are not listed here) will be considered to be fine.
$aCheck = [
    'GET'    => [
        'idart'      => cRequestValidator::CHECK_INTEGER,
        'idcat'      => cRequestValidator::CHECK_INTEGER,
        'idartlang'  => cRequestValidator::CHECK_INTEGER,
        'idcatart'   => cRequestValidator::CHECK_INTEGER,
        'lang'       => cRequestValidator::CHECK_INTEGER,
        'changelang' => cRequestValidator::CHECK_INTEGER,
        'idcatlang'  => cRequestValidator::CHECK_INTEGER,

        'client' => cRequestValidator::CHECK_INTEGER,
        'area'   => cRequestValidator::CHECK_AREASTRING,
        'frame'  => cRequestValidator::CHECK_INTEGER,

        'tmpchangelang' => cRequestValidator::CHECK_INTEGER,
        'changeview'    => cRequestValidator::CHECK_PRIMITIVESTRING,
        'action'        => cRequestValidator::CHECK_PRIMITIVESTRING,
        'changeclient'  => cRequestValidator::CHECK_INTEGER,
        // 'contenido'     => cRequestValidator::CHECK_HASH32,

        'page' => cRequestValidator::CHECK_INTEGER,

        'catname' => cRequestValidator::CHECK_PRIMITIVESTRING,

        'belang' => cRequestValidator::CHECK_BELANG,
        'path'   => cRequestValidator::CHECK_PATHSTRING,
    ],
    'POST'   => [
        'idart'      => cRequestValidator::CHECK_INTEGER,
        'idcat'      => cRequestValidator::CHECK_INTEGER,
        'idartlang'  => cRequestValidator::CHECK_INTEGER,
        'idcatart'   => cRequestValidator::CHECK_INTEGER,
        'lang'       => cRequestValidator::CHECK_INTEGER,
        'changelang' => cRequestValidator::CHECK_INTEGER,
        'idcatlang'  => cRequestValidator::CHECK_INTEGER,

        'client' => cRequestValidator::CHECK_INTEGER,
        'area'   => cRequestValidator::CHECK_AREASTRING,
        'frame'  => cRequestValidator::CHECK_INTEGER,

        'tmpchangelang' => cRequestValidator::CHECK_INTEGER,
        'changeview'    => cRequestValidator::CHECK_PRIMITIVESTRING,
        'action'        => cRequestValidator::CHECK_PRIMITIVESTRING,
        'changeclient'  => cRequestValidator::CHECK_INTEGER,
        'page'          => cRequestValidator::CHECK_INTEGER,

        'catname'   => cRequestValidator::CHECK_PRIMITIVESTRING,
        // 'contenido' => cRequestValidator::CHECK_HASH32,

        'belang' => cRequestValidator::CHECK_BELANG,
        'path'   => cRequestValidator::CHECK_PATHSTRING,
    ],
    'COOKIE' => [
        'idart'      => cRequestValidator::CHECK_INTEGER,
        'idcat'      => cRequestValidator::CHECK_INTEGER,
        'idartlang'  => cRequestValidator::CHECK_INTEGER,
        'idcatart'   => cRequestValidator::CHECK_INTEGER,
        'lang'       => cRequestValidator::CHECK_INTEGER,
        'changelang' => cRequestValidator::CHECK_INTEGER,
        'idcatlang'  => cRequestValidator::CHECK_INTEGER,

        'client' => cRequestValidator::CHECK_INTEGER,
        'area'   => cRequestValidator::CHECK_AREASTRING,
        'frame'  => cRequestValidator::CHECK_INTEGER,

        'tmpchangelang' => cRequestValidator::CHECK_INTEGER,
        'changeview'    => cRequestValidator::CHECK_PRIMITIVESTRING,
        'action'        => cRequestValidator::CHECK_PRIMITIVESTRING,
        'changeclient'  => cRequestValidator::CHECK_INTEGER,
        'page'          => cRequestValidator::CHECK_INTEGER,

        'catname'   => cRequestValidator::CHECK_PRIMITIVESTRING,
        // 'contenido' => cRequestValidator::CHECK_HASH32,

        'belang' => cRequestValidator::CHECK_BELANG,
        'path'   => cRequestValidator::CHECK_PATHSTRING,
    ],
];

?>