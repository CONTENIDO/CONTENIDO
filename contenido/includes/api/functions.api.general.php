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
 *   modified 2009-10-27, Murat Purc, initialization of variable $error to prevent PHP strict messages
 *   modified 2010-05-20, Murat Purc, standardized Contenido startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// @TODO: PATH_SEPARATOR/DIRECTORY_SEPARATOR exists since PHP 4.3.4, Contenido requires PHP 5...
if (!defined('PATH_SEPARATOR')) {
    define('PATH_SEPARATOR', (substr(PHP_OS, 0, 3) == 'WIN') ? ';' : ':');
}

if (!defined('DIRECTORY_SEPARATOR')) {
    define('DIRECTORY_SEPARATOR', (substr(PHP_OS, 0, 3) == 'WIN') ? '\\' : '/');
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
 * contenido_include: Includes a file
 * and takes care of all path transformations.
 *
 * Example:
 * contenido_include("classes", "class.backend.php");
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
 *       class files, there is no need to load class files by using
 *       contenido_include() or cInclude().
 *
 * @param $where string The area which should be included
 * @param $what string The filename of the include
 * @param $force boolean If true, force the file to be included
 * @param $returnpath $string or boolean false if file is not found
 *
 * @return none
 *
 */
function contenido_include ($where, $what, $force = false, $returnpath = false)
{
   global $client, $cfg, $cfgClient;

   /* Sanity check for $what */
   $what = trim($what);
   $where = strtolower($where);
   $error = false;

   switch ($where)

   {
      case "frontend":
            $include = $cfgClient[$client]["path"]["frontend"] . $what;
            break;
      case "wysiwyg":
            $include = $cfg['path']['wysiwyg'] . $what;
            break;
      case "all_wysiwyg":
      		$include = $cfg['path']['all_wysiwyg'] . $what;
      		break;
      case "conlib":
      case "phplib":
            $include = $cfg['path']['phplib'] . $what;
            break;
      case "pear":
            $include = $what;
            $include_path = ini_get('include_path');

            if (!preg_match("|".$cfg['path']['pear']."|i", $include_path)) {
               // contenido pear path is not set in include_path

               // we try to add it via ini_set
               if (!@ini_set( 'include_path' , $include_path.PATH_SEPARATOR.$cfg['path']['pear'])) {
                  // not able to change include_path
                  trigger_error("Can't add {$cfg['path']['pear']} to include_path", E_USER_NOTICE);
                  $include = $cfg['path']['pear'] . $what; unset($where);
               } else {

                  $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
                  $last = count($paths)-1;
                  if ($last >= 2) {
                     $tmp = $paths[1];
                     $paths[1] = $paths[$last];
                     $paths[$last] = $tmp;
                     @ini_set( 'include_path' ,implode(PATH_SEPARATOR, $paths));

                  }
                  unset ($paths, $last, $tmp);

               }
            }
            break;
      default:
            // FIXME: A workaround to provide inclusion of classes wich are not
            //        handled by the autoloader
            if ($where === 'classes') {
                if (Contenido_Autoload::isAutoloadable($cfg['path'][$where] . $what)) {
                    // it's a class file and it will be loaded automatically by 
                    // the autoloader - get out here
                    return;
                }
            }
            $include = $cfg['path']['contenido'] . $cfg['path'][$where] . $what;
            break;
   }

   if ( $where == "pear" ) {

      // now we check if the file is available in the include path
      $foundinpath = false;
      $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
      foreach ($paths as $path) {
         if (@file_exists($path . DIRECTORY_SEPARATOR . $include) && !$foundinpath) {
            $foundinpath = $path;
         }
      }

      if (!$foundinpath)
      {
         $error = true;
      }

      unset ($paths, $path);

   } else {

      if (!file_exists($include) || preg_match("#^\.\./#",$what))
      {
         $error = true;
      }

   }

   if ($returnpath) {

      if ($foundinpath) {
         $include = $foundinpath . DIRECTORY_SEPARATOR . $include;
      }

      if (!$error) {
         return $include;
      } else {
         return false;
      }
   }

   if ($error) {
      trigger_error("Error: Can't include $include", E_USER_ERROR);
      return;
   }

   if ($force == true)
   {
      include($include);
   } else {
      include_once($include);
   }

}


/**
 * cInclude: Shortcut to contenido_include.
 *
 * @see contenido_include
 *
 * @param $where string The area which should be included
 * @param $what string The filename of the include
 * @param $force boolean If true, force the file to be included
 *
 * @return none
 *
 */
function cInclude ($where, $what, $force = false)
{
	contenido_include($where, $what, $force);
}

/**
 * plugin_include: Includes a file
 * from a plugin and takes care of all
 * path transformations.
 *
 * Example:
 * plugin_include("formedit", "classes/class.formedit.php");
 *
 * @param $which string The name of the plugin
 * @param $what string The filename of the include
 *
 * @return none
 *
 */
function plugin_include ($where, $what)
{
	global $cfg;

	$include = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $where. "/" . $what;

	include_once($include);
}



?>