<?php

/**
 * This file contains CONTENIDO General API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generally usable
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Includes a file and takes care of all path transformations.
 *
 * Example:
 * cInclude('classes', 'class.backend.php');
 *
 * Supported areas to include files within:
 *
 * - frontend    Path to the *current* frontend
 * - classes     Path to the CONTENIDO classes (see NOTE below)
 * - cronjobs    Path to the cronjobs
 * - external    Path to the external tools
 * - includes    Path to the CONTENIDO includes
 * - scripts     Path to the CONTENIDO scripts
 * - module      Path to module
 *
 * NOTE: Since CONTENIDO (since v 4.9.0) provides autoloading of required class files,
 *       there is no need to load CONTENIDO class files by using cInclude().
 *
 * @param string $where The area which should be included
 * @param string $what The name of the file to include
 * @param bool $force If true, force the file to be included
 * @param bool $returnPath Flag to return the path instead of including the file
 * @return bool|string|NULL
 * @throws cInvalidArgumentException|cException
 */
function cInclude($where, $what, $force = false, $returnPath = false)
{
    // NOTE: Use global here, the included file may need this!
    global $client, $cfg, $cfgClient, $cCurrentModule;

    $backendPath = cRegistry::getBackendPath();

    // Sanity check for $what
    $what = trim($what);
    $where = cString::toLowerCase($where);
    $isError = false;

    switch ($where) {
        case 'module':
            $handler = new cModuleHandler($cCurrentModule);
            $include = $handler->getPhpPath() . $what;
            break;
        case 'frontend':
            $include = cRegistry::getFrontendPath() . $what;
            break;
        case 'classes':
            if (cAutoload::isAutoloadable($cfg['path'][$where] . $what)) {
                // The class file will be loaded automatically by the autoloader - get out here
                return NULL;
            }
            $include = $backendPath . $cfg['path'][$where] . $what;
            break;
        default:
            $include = $backendPath . $cfg['path'][$where] . $what;
            break;
    }

    if (!cFileHandler::exists($include) || preg_match('#^\.\./#', $what)) {
        $isError = true;
    }

    // should the path be returned?
    if ($returnPath) {
        return !$isError ? $include : false;
    }

    if ($isError) {
        cError("Error: Can't include $include", E_USER_ERROR);
        return false;
    }

    // now include the file
    if ($force) {
        return include($include);
    } else {
        return include_once($include);
    }

}

/**
 * Includes a file from a plugin and takes care of all path transformations.
 *
 * Example:
 * plugin_include('formedit', 'classes/class.formedit.php');
 *
 * @param string $where The name of the plugin
 * @param string $what The name of the file to include
 */
function plugin_include($where, $what)
{
    $cfg = cRegistry::getConfig();

    $sInclude = cRegistry::getBackendPath() . $cfg['path']['plugins'] . $where . '/' . $what;

    include_once($sInclude);
}
