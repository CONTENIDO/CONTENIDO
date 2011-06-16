<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido General API functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2003-09-01
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2009-10-27, Murat Purc, initialization of variable $bError to prevent PHP strict messages
 *   modified 2010-05-20, Murat Purc, standardized Contenido startup and security check invocations, see [#CON-307]
 *   modified 2011-06-14, Rusmir Jusufovic, add a new path to cInclude, "modul" path to the php directory in current modul
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/* Info:
 * This file contains Contenido General API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 */

/**
 * Includes a file and takes care of all path transformations.
 *
 * Example:
 * contenido_include('classes', 'class.backend.php');
 *
 * Currently defined areas:
 *
 * frontend    Path to the *current* frontend
 * conlib      Path to conlib
 * pear        Path to the bundled pear copy
 * classes     Path to the contenido classes (see NOTE below)
 * cronjobs    Path to the cronjobs
 * external    Path to the external tools
 * includes    Path to the contenido includes
 * scripts     Path to the contenido scripts
 *
 * NOTE: Since Contenido (since v 4.8.15) provides autoloading of required
 *       class files, there is no need to load Contenido class files of by using
 *       contenido_include() or cInclude().
 *
 * @param   string  $sWhere       The area which should be included
 * @param   string  $sWhat        The filename of the include
 * @param   bool    $bForce       If true, force the file to be included
 * @param   string  $bReturnPath  Flag to return the path instead of including the file
 * @return  void
 */
function contenido_include($sWhere, $sWhat, $bForce = false, $bReturnPath = false)
{
   global $client, $cfg, $cfgClient,$cCurrentModule;

    // Sanity check for $sWhat
    $sWhat  = trim($sWhat);
    $sWhere = strtolower($sWhere);
    $bError = false;

    switch ($sWhere) {

		case "modul":
   			#Contenido_Vars::debugg();
   			$handler = new Contenido_Module_Handler($cCurrentModule);
   			$sInclude = $handler->getPhpPath().$sWhat;
   		break;

        case 'frontend':
            $sInclude = $cfgClient[$client]['path']['frontend'] . $sWhat;
            break;
        case 'wysiwyg':
            $sInclude = $cfg['path']['wysiwyg'] . $sWhat;
            break;
        case 'all_wysiwyg':
            $sInclude = $cfg['path']['all_wysiwyg'] . $sWhat;
            break;
        case 'conlib':
        case 'phplib':
            $sInclude = $cfg['path']['phplib'] . $sWhat;
            break;
        case 'pear':
            $sInclude = $sWhat;
            $sIncludePath = ini_get('include_path');

            if (!preg_match('|' . $cfg['path']['pear'] . '|i', $sIncludePath)) {
                // contenido pear path is not set in include_path
                // we try to add it via ini_set
                if (!@ini_set('include_path', $sIncludePath . PATH_SEPARATOR . $cfg['path']['pear'])) {
                    // not able to change include_path
                    trigger_error("Can't add {$cfg['path']['pear']} to include_path", E_USER_NOTICE);
                    $sInclude = $cfg['path']['pear'] . $sWhat;
                    unset($sWhere);
                } else {
                    $aPaths = explode(PATH_SEPARATOR, ini_get('include_path'));
                    $iLast  = count($aPaths) - 1;
                    if ($iLast >= 2) {
                        $tmp = $aPaths[1];
                        $aPaths[1] = $aPaths[$iLast];
                        $aPaths[$iLast] = $tmp;
                        @ini_set('include_path', implode(PATH_SEPARATOR, $aPaths));
                    }
                    unset($aPaths, $iLast, $tmp);
                }
            }
            break;
        default:
            // FIXME: A workaround to provide inclusion of classes which are not
            //        handled by the autoloader
            if ($sWhere === 'classes') {
                if (Contenido_Autoload::isAutoloadable($cfg['path'][$sWhere] . $sWhat)) {
                    // it's a class file and it will be loaded automatically by
                    // the autoloader - get out here
                    return;
                }
            }
            $sInclude = $cfg['path']['contenido'] . $cfg['path'][$sWhere] . $sWhat;
            break;
    }

    $sFoundPath = '';

    if ($sWhere === 'pear') {
        // now we check if the file is available in the include path
        $aPaths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($aPaths as $sPath) {
            if (@file_exists($sPath . DIRECTORY_SEPARATOR . $sInclude)) {
                $sFoundPath = $sPath;
                break;
            }
        }

        if (!$sFoundPath) {
            $bError = true;
        }

        unset($aPaths, $sPath);
    } else {
        if (!file_exists($sInclude) || preg_match('#^\.\./#', $sWhat)) {
            $bError = true;
        }
    }

    // should the path be returned?
    if ($bReturnPath) {
        if ($sFoundPath !== '') {
            $sInclude = $sFoundPath . DIRECTORY_SEPARATOR . $sInclude;
        }

        if (!$bError) {
            return $sInclude;
        } else {
            return false;
        }
    }

    if ($bError) {
        trigger_error("Error: Can't include $sInclude", E_USER_ERROR);
        return;
    }

    // now include the file
    if ($bForce == true) {
        include($sInclude);
    } else {
        include_once($sInclude);
    }
}


/**
 * Shortcut to contenido_include.
 *
 * @see contenido_include
 *
 * @param   string  $sWhere  The area which should be included
 * @param   string  $sWhat   The filename of the include
 * @param   bool    $bForce  If true, force the file to be included
 * @return  void
 */
function cInclude($sWhere, $sWhat, $bForce = false)
{
    contenido_include($sWhere, $sWhat, $bForce);
}

/**
 * Includes a file from a plugin and takes care of all path transformations.
 *
 * Example:
 * plugin_include('formedit', 'classes/class.formedit.php');
 *
 * @param   string  $sWhere  The name of the plugin
 * @param   string  $sWhat   The filename of the include
 * @return  void
 */
function plugin_include($sWhere, $sWhat)
{
    global $cfg;

    $sInclude = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $sWhere. '/' . $sWhat;

    include_once($sInclude);
}


?>