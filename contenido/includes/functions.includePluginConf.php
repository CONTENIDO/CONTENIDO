<?php
// added 2008-06-16, H. Librenz - Hotfix: checking for dirty calls!
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
