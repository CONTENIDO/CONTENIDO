<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Include Plugins Functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.4
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$pluginorder = getSystemProperty("system", "plugin-order");

$plugins = explode(",", $pluginorder);

$ipc_conpluginpath = $cfg["path"]["contenido"] . $cfg["path"]["plugins"];

// Scan and save only by the BE
if ($contenido) {
    $lastscantime = getSystemProperty("system", "plugin-lastscantime");

    // Clean up: Fetch and trim the plugin order
    $plugins = array();

    if ($pluginorder != "") {
        $plugins = explode(",", $pluginorder);

        foreach ($plugins as $key => $plugin) {
            $plugins[$key] = trim($plugin);
        }
    }

    // Don't scan all the time, but each 60 seconds
    if ($lastscantime +60 < time()) {

        // Directories which are to exclude from scanning process
        $dirsToExclude = trim(getSystemProperty('system', 'plugin-dirstoexclude'));
        if ($dirsToExclude === '') {
            $dirsToExclude = '.,..,.svn,.cvs,includes';
            setSystemProperty('system', 'plugin-dirstoexclude', $dirsToExclude);
        }
        $dirsToExclude = explode(',', $dirsToExclude);
        foreach ($dirsToExclude as $pos => $item) {
            $dirsToExclude[$pos] = trim($item);
        }

        // scan for new Plugins
        $dh = opendir($ipc_conpluginpath);
        while (($file = readdir($dh)) !== false) {
            if (is_dir($ipc_conpluginpath . $file) &&
                !in_array(strtolower($file), $dirsToExclude) &&
                !in_array($file, $plugins))
            {
                $plugins[] = $file;
            }
        }
        closedir($dh);

        setSystemProperty("system", "plugin-lastscantime", time());


        // Remove plugins do not exist
        foreach ($plugins as $key => $ipc_plugin) {
            if (!is_dir($ipc_conpluginpath . $ipc_plugin . "/") ||
                in_array($ipc_plugin, $dirsToExclude))
            {
                unset($plugins[$key]);
            }
        }

        // Save Scanresult
        $pluginorder = implode(",", $plugins);
        setSystemProperty("system", "plugin-order", $pluginorder);
    }
}


// Load Plugin-Config and Plugin-Translation
foreach ($plugins as $key => $ipc_plugin) {
    if (!is_dir($ipc_conpluginpath . $ipc_plugin . "/")) {
        unset($plugins[$key]);
    } else {
        $ipc_localedir  = $ipc_conpluginpath . $ipc_plugin . "/locale/";
        $ipc_langfile   = $ipc_conpluginpath . $ipc_plugin . "/includes/language.plugin.php";
        $ipc_configfile = $ipc_conpluginpath . $ipc_plugin . "/includes/config.plugin.php";

        if (cFileHandler::exists($ipc_localedir)) {
            i18nRegisterDomain($ipc_plugin, $ipc_localedir);
        }
        if (cFileHandler::exists($ipc_langfile)) {
            include_once($ipc_langfile);
        }
        if (cFileHandler::exists($ipc_configfile)) {
            include_once($ipc_configfile);
        }
    }
}

unset($plugins);

?>