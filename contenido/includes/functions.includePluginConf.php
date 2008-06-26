<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Include Plugins Functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-16, Holger Librenz, Hotfix: checking for dirty calls!
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}

/**
 * very dirty hack
 */
$ipc_conpluginpath = $cfg['path']['contenido'].$cfg["path"]['plugins'];

    $ipc_dh = opendir($ipc_conpluginpath);

    while (($ipc_plugin = readdir($ipc_dh)) !== false)
    {
       if (is_dir($ipc_conpluginpath.$ipc_plugin)  && $ipc_plugin != ".." && $ipc_plugin != ".")
          {
            $ipc_configfile = $ipc_conpluginpath.$ipc_plugin. "/includes/config.plugin.php";
         $ipc_langfile   = $ipc_conpluginpath.$ipc_plugin. "/includes/language.plugin.php";
         $ipc_localedir  = $ipc_conpluginpath.$ipc_plugin. "/locale/";

            if (file_exists($ipc_localedir))
           {
               i18nRegisterDomain($ipc_plugin, $ipc_localedir);
           }

           if (file_exists($ipc_langfile))
           {
               include_once($ipc_langfile);
           }
           if (file_exists($ipc_configfile))
           {
               include_once($ipc_configfile);
           }
       }
    }

closedir($ipc_dh);
?>