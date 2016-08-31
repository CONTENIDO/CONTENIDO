<?php
/**
 * Detection of PHP dependency in CONTENIDO.
 *
 * Uses PEAR package PHP_CompatInfo, see http://pear.php.net/package/PHP_CompatInfo
 * Requires the PEAR package PHP_CompatInfo!
 *
 * PHP_CompatInfo parses the complete CONTENIDO project folder recursively and
 * collects all dependency informations.
 *
 * Usage:
 * ------
 * Call this script from command line as follows:
 *     $ php phpcompat.php
 *
 * NOTE:
 * Pass the output into a file using following commnad:
 *     $ php phpcompat.php > phpcompat_info.txt
 *
 * @package          Core
 * @subpackage       Tool
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

// allow execution only thru cli mode
if (substr(PHP_SAPI, 0, 3) != 'cli') {
    die('Illegal call');
}

// /////////////////////////////////////////////////////////////////////
// Initialization/Settings

// create a page context class, better than spamming global scope
$context = new stdClass();

// CONTENIDO installation path (folder which contains "cms", "contenido", "docs", "setup", etc...)
$context->contenidoInstallPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../')) . '/';

// /////////////////////////////////////////////////////////////////////
// Proccess

require_once 'PHP/CompatInfo.php';
$context->info = new PHP_CompatInfo();
$context->info->parseDir($context->contenidoInstallPath);

// /////////////////////////////////////////////////////////////////////
// Shutdown

unset($context);
