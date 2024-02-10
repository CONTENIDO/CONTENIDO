<?php

/**
 * This file contains Plugin Manager Backend View.
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cApiUser $currentuser
 * @var cSession $sess
 * @var array $cfg
 */

// initializing classes
$page = new cGuiPage('pim_overview', 'pim');

$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());

// Display critical error if no valid client/language is selected
if ($client < 1 || $lang < 1) {
    $message = $client < 1 ? i18n('No Client selected') : i18n('No language selected');
    $page->displayCriticalError($message);
    $page->render();
    exit();
}

// access denied
if (!$perm->isSysadmin($currentuser)) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    exit();
}

// check disable plugin var
if ($cfg['debug']['disable_plugins'] === true) {
    $page->displayWarning(i18n('Currently the plugin system is disabled via configuration', 'pim'));
}

// initializing PimPluginSetup class
$setup = new PimPluginSetup();
$setup->setPageClass($page);

// initializing PimPluginViewDependencies class
$pluginDependenciesView = new PimPluginViewDependencies();

// initializing PimPluginViewNavSub class
$navSubView = new PimPluginViewNavSub();

$viewAction = $_REQUEST['pim_view'] ?? 'overview';

switch ($viewAction) {
    case 'activestatus':
        $status = new PimPluginSetupStatus();
        $status->changeActiveStatus($_GET['pluginId']);
        break;
    case 'update':
        // Set mode to update
        $setup->setMode('update');
        $setup->setPluginId($_POST['pluginId']);

        // Check Xml
        if ($setup->checkXml()) {
            $update = new PimPluginSetupUpdate();
        }

        break;
    case 'uninstall':
        $setup->setMode('uninstall');
        $setup->setPluginId($_GET['pluginId']);
        $delete = new PimPluginSetupUninstall();
        if ($_GET['uninstallsql'] == '1') {
            $delete->uninstall(true);
        } else {
            $delete->uninstall(false);
        }
        break;
    case 'uninstall-extracted':
        $setup->setMode('uninstall');
        $delete = new PimPluginSetupUninstall();
        $delete->setPluginFoldername($_GET['pluginFoldername']);
        $delete->uninstallDir();
        break;
    case 'install':
        $setup->setMode('uploaded');

        if ($setup->checkXml()) {
            $new = new PimPluginSetupInstall();
            $new->install();
        }
        break;
    case 'install-extracted':
        $setup->setMode('extracted');
        if ($setup->checkXml()) {
            $new = new PimPluginSetupInstall();
            $new->install();
        }
        break;
}

// path to pim template files
$tempTplPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'pim/templates';

// initializing array for installed plugins
$installedPluginFoldernames = [];

// get all installed plugins
$oItem = new PimPluginCollection();
$oItem->select(NULL, NULL, 'executionorder');
$pluginsInstalled = '';

while (($plugin = $oItem->next()) !== false) {

    // initialization new template class
    $pagePlugins = new cTemplate();

    // id of plugin
    $idplugin = $plugin->get('idplugin');

    // changed foldername for nav_sub view class
    $navSubView->setPluginFoldername($plugin->get('folder'));

    // display plugin dependencies
    $pagePlugins->set('s', 'DEPENDENCIES', $pluginDependenciesView->getPluginDependenciesInstalled($idplugin));

    // display navigation entries
    $pagePlugins->set('s', 'NAVSUB', $navSubView->getNavSubentries());

    // date
    $date = date_format(date_create($plugin->get('installed')), i18n('Y-m-d', 'pim'));

    $pagePlugins->set('s', 'IDPLUGIN', $idplugin);
    $pagePlugins->set('s', 'FOLDERNAME', $plugin->get('folder'));
    $pagePlugins->set('s', 'NAME', $plugin->get('name'));
    $pagePlugins->set('s', 'VERSION', $plugin->get('version'));
    $pagePlugins->set('s', 'DESCRIPTION', $plugin->get('description'));
    $pagePlugins->set('s', 'AUTHOR', $plugin->get('author'));
    $pagePlugins->set('s', 'MAIL', $plugin->get('mail'));
    $pagePlugins->set('s', 'WEBSITE', $plugin->get('website'));
    $pagePlugins->set('s', 'COPYRIGHT', $plugin->get('copyright'));
    $pagePlugins->set('s', 'INSTALLED', $date);
    $pagePlugins->set('s', 'EXECUTIONORDER', $plugin->get("executionorder"));

    $pagePlugins->set('s', 'LANG_SORT_DOWN', i18n('Set execution order down', 'pim'));
    $pagePlugins->set('s', 'LANG_SORT_UP', i18n('Set execution order up', 'pim'));

    $pagePlugins->set('s', 'LANG_INSTALLED', i18n('Installed since', 'pim'));
    $pagePlugins->set('s', 'LANG_AUTHOR', i18n('Author', 'pim'));
    $pagePlugins->set('s', 'LANG_CONTACT', i18n('Contact', 'pim'));

    $pagePlugins->set('s', 'LANG_UPDATE', i18n('Update', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE_CHOOSE', i18n('Please choose your new file', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE_UPLOAD', i18n('Update', 'pim'));
    $pagePlugins->set('s', 'LANG_REMOVE_SQL', i18n('Execute plugin_uninstall.sql', 'pim'));

    $pagePlugins->set('s', 'LANG_DEPENDENCIES', i18n('Dependencies', 'pim'));

    // enable / disable functionality
    $activeStatus = $plugin->get('active');
    $tempActiveStatusLink = $sess->url('main.php?area=pim&frame=4&pim_view=activestatus&pluginId=' . $idplugin);
    if ($activeStatus == 1) {
        $pagePlugins->set('s', 'DATA_ACTIVESTATUS', '1');
        $pagePlugins->set('s', 'ACTIVATE_INACTIVATE_LINK', '<a class="con_func_button" href="' . $tempActiveStatusLink . '"><img src="images/online.gif" alt="" /> ' . i18n('Disable', 'pim') . '</a>');
    } else {
        $pagePlugins->set('s', 'DATA_ACTIVESTATUS', '0');
        $pagePlugins->set('s', 'ACTIVATE_INACTIVATE_LINK', '<a class="con_func_button" href="' . $tempActiveStatusLink . '"><img src="images/offline.gif" alt=""/> ' . i18n('Enable', 'pim') . '</a>');
    }

    // uninstall link
    $tempUninstallLink = $sess->url('main.php?area=pim&frame=4&pim_view=uninstall&uninstallsql=1&pluginId=' . $idplugin);
    $pagePlugins->set('s', 'UNINSTALL_LINK', '<a class="con_func_button" href="javascript:void(0)" data-action="uninstall_plugin" data-href="' . $tempUninstallLink . '"><img src="images/but_cancel.gif" alt=""> ' . i18n('Uninstall', 'pim') . '</a>');

    // put foldername into array installedPluginFoldernames
    $installedPluginFoldernames[] = $plugin->get('folder');

    $pluginsInstalled .= $pagePlugins->generate($tempTplPath . '/template.pim_plugins_installed.html', true, false);
}

$pluginsExtracted = '';

// get extracted plugins
if (is_dir($cfg['path']['plugins'])) {
    if (false !== ($handle = cDirHandler::read($cfg['path']['plugins']))) {

        $i = 0;
        foreach ($handle as $pluginFoldername) {
            $pluginPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . $pluginFoldername;
            $tempPath = $pluginPath . '/plugin.xml';

            if (is_dir($pluginPath) && cFileHandler::exists($tempPath) && !in_array($pluginFoldername, $installedPluginFoldernames)) {
                // initialization of a new template class
                $pagePlugins = new cTemplate();

                // Read plugin.xml
                $tempXmlContent = cFileHandler::read($tempPath);

                // Write plugin.xml content into temporary variable
                $tempXml = simplexml_load_string($tempXmlContent);

                $pagePlugins->set('s', 'LANG_FOLDERNAME', i18n('Foldername', 'pim'));
                $pagePlugins->set('s', 'LANG_AUTHOR', i18n('Author', 'pim'));
                $pagePlugins->set('s', 'LANG_CONTACT', i18n('Contact', 'pim'));
                $pagePlugins->set('s', 'LANG_TOOLTIP_INSTALL', i18n('Install extracted plugin', 'pim'));
                $pagePlugins->set('s', 'LANG_INSTALL', i18n('Install', 'pim'));
                $pagePlugins->set('s', 'LANG_TOOLTIP_REMOVE', i18n('Uninstall extracted plugin (deleted plugin files from filesystem)', 'pim'));
                $pagePlugins->set('s', 'LANG_REMOVE', i18n('Remove from filesystem', 'pim'));
                $pagePlugins->set('s', 'LANG_DEPENDENCIES', i18n('Dependencies', 'pim'));
                $pagePlugins->set('s', 'LANG_WRITEABLE', i18n('Writeable', 'pim'));
                $pagePlugins->set('s', 'FOLDERNAME', $pluginFoldername);
                $pagePlugins->set('s', 'INSTALL_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=install-extracted&pluginFoldername=' . $pluginFoldername));

                // Values from plugin.xml
                $pagePlugins->set('s', 'PLUGIN_NAME', $tempXml->general->plugin_name);
                $pagePlugins->set('s', 'VERSION', $tempXml->general->version);
                $pagePlugins->set('s', 'DESCRIPTION', $tempXml->general->description);
                $pagePlugins->set('s', 'AUTHOR', $tempXml->general->author);
                $pagePlugins->set('s', 'COPYRIGHT', $tempXml->general->copyright);
                $pagePlugins->set('s', 'MAIL', $tempXml->general->mail);
                $pagePlugins->set('s', 'WEBSITE', $tempXml->general->website);
                $pagePlugins->set('s', 'DEPENDENCIES', $pluginDependenciesView->getPluginDependenciesExtracted($tempXml));

                // uninstall link
                if (is_writable(cRegistry::getBackendPath() . $cfg['path']['plugins'] . $pluginFoldername)) {
                    $pagePlugins->set('s', 'REMOVE_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=uninstall-extracted&pluginFoldername=' . $pluginFoldername));
                    $pagePlugins->set('s', 'WRITEABLE', i18n('Everything looks fine', 'pim'));
                } else {
                    $pagePlugins->set('s', 'REMOVE_LINK', 'javascript:void(0)');
                    $pagePlugins->set('s', 'WRITEABLE', '(<span class="settingWrong">' . i18n('This plugin is not writeable, please set the rights manually', 'pim') . '</span>)');
                }

                $pagePlugins->set('s', 'IDPLUGIN', $i++);
                $pluginsExtracted .= $pagePlugins->generate($tempTplPath . '/template.pim_plugins_extracted.html', true, false);
            }
        }
    }
}

// if pluginsExtracted var is empty
if (empty($pluginsExtracted)) {
    $pluginsExtracted = i18n('No entries', 'pim');
}

// Set page name
$page->set('s', 'PAGE_NAME', 'pim_overview');

// Set language vars
$page->set('s', 'LANG_UPLOAD', i18n('Upload a new plugin', 'pim'));
$page->set('s', 'LANG_UPLOAD_CHOOSE', i18n('Please choose a plugin package', 'pim'));
$page->set('s', 'LANG_UPLOAD_BUTTON', i18n('Upload plugin package', 'pim'));
$page->set('s', 'LANG_INSTALLED', i18n('Installed Plugins', 'pim'));
$page->set('s', 'LANG_EXTRACTED', i18n('Not installed Plugins', 'pim'));
$page->set('s', 'LANG_EXECUTIONORDERINFO_TITLE', i18n('Execution order', 'pim'));
$page->set('s', 'LANG_EXECUTIONORDERINFO_TEXT', i18n('If you click on following arrows you can manage the execution order of plugins. The first plugin (at first position) will be executed first from CONTENIDO, the last plugin (at last position) will be executed at the end. This can be important if CONTENIDO backend has to load a plugin to activate other functions/plugins. Normally the execution order has no impact.', 'pim'));
$page->set('s', 'LANG_EXPAND_COLLAPSE_ALL', i18n('Expand/collapse all', 'pim'));
$page->set('s', 'LANG_FILTER', i18n('Filter', 'pim'));
$page->set('s', 'LANG_ALL', i18n('all', 'pim'));
$page->set('s', 'LANG_ACTIVE', i18n('active', 'pim'));
$page->set('s', 'LANG_INACTIVE', i18n('inactive', 'pim'));
$page->set('s', 'LANG_ERROR', i18n('Error', 'pim'));
$page->set('s', 'LANG_PLUGIN_ALREADY_AT_BOTTOM', i18n('This plugin is already at the bottom!', 'pim'));
$page->set('s', 'LANG_PLUGIN_ALREADY_AT_TOP', i18n('This plugin is already at the top!', 'pim'));
$page->set('s', 'LANG_UNINSTALL_PLUGIN_CONFIRMATION', i18n('Are you sure to uninstall this plugin? Files are not deleted in filesystem.', 'pim'));
$page->set('s', 'LANG_REMOVE_PLUGIN_CONFIRMATION', i18n('Are you sure you want to remove this plugin? All plugin files will be irretrievably removed from the file system.', 'pim'));
$page->set('s', 'LANG_SELECTION_UNINSTALL_SQL_INFO', i18n('With the selection `execute plugin_uninstall.sql` any database tables/entries of the plugin will be removed from the database.', 'pim'));

// Set information about installed plugins
$page->set('s', 'INSTALLED_PLUGINS', $oItem->count());
$page->set('s', 'PLUGINS_INSTALLED', $pluginsInstalled);
$page->set('s', 'PLUGINS_EXTRACTED', $pluginsExtracted);

$page->render();
