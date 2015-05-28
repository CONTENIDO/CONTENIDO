<?php

/**
 * This file contains Plugin Manager Backend View.
 *
 * @package Plugin
 * @subpackage PluginManager
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// initializing classes
$page = new cGuiPage('pim_overview', 'pim');

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

$viewAction = isset($_REQUEST['pim_view']) ? $_REQUEST['pim_view'] : 'overview';

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
        $setup->checkXml();

        $update = new PimPluginSetupUpdate();
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
        $setup->checkXml();
        $new = new PimPluginSetupInstall();
        $new->install();
        break;
    case 'install-extracted':
        $setup->setMode('extracted');
        $setup->checkXml();
        $new = new PimPluginSetupInstall();
        $new->install();
        break;
}

// path to pim template files
$tempTplPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'pim/templates';

// initializing array for installed plugins
$installedPluginFoldernames = array();

// get all installed plugins
$oItem = new PimPluginCollection();
$oItem->select(NULL, NULL, 'executionorder');

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
        $pagePlugins->set('s', 'LANG_ACTIVESTATUS', '<img src="images/online.gif" alt="" class="vAlignMiddle" /> <a href="' . $tempActiveStatusLink . '">' . i18n('Disable', 'pim') . '</a>');
    } else {
        $pagePlugins->set('s', 'LANG_ACTIVESTATUS', '<img src="images/offline.gif" alt="" class="vAlignMiddle" /> <a href="' . $tempActiveStatusLink . '">' . i18n('Enable', 'pim') . '</a>');
    }

    // uninstall link
    $tempUninstallLink = $sess->url('main.php?area=pim&frame=4&pim_view=uninstall&uninstallsql=1&pluginId=' . $idplugin);
    $pagePlugins->set('s', 'UNINSTALL_LINK', '<a id="removalLink-' . $plugin->get('idplugin') . '" href="javascript:void(0)" onclick="javascript:Con.showConfirmation(\'' . i18n('Are you sure to delete this plugin? Files are not deleted in filesystem.', 'pim') . '\', function() { window.location.href=\'' . $tempUninstallLink . '\';})">' . i18n('Uninstall', 'pim') . '</a>');

    // put foldername into array installedPluginFoldernames
    $installedPluginFoldernames[] = $plugin->get('folder');

    $pluginsInstalled .= $pagePlugins->generate($tempTplPath . '/template.pim_plugins_installed.html', true, false);
}

// get extracted plugins
if (is_dir($cfg['path']['plugins'])) {
    if (false !== ($handle = cDirHandler::read($cfg['path']['plugins']))) {

        $i = 0;
        foreach ($handle as $pluginFoldername) {
            $pluginPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $pluginFoldername;
            $tempPath = $pluginPath . '/plugin.xml';

            if (is_dir($pluginPath) && cFileHandler::exists($tempPath) && !in_array($pluginFoldername, $installedPluginFoldernames)) {
                // initalization new template class
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
                if (is_writable($cfg['path']['contenido'] . $cfg['path']['plugins'] . $pluginFoldername)) {
                    $pagePlugins->set('s', 'REMOVE_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=uninstall-extracted&pluginFoldername=' . $pluginFoldername));
                    $pagePlugins->set('s', 'WRITEABLE', i18n('Everything looks fine', 'pim'));
                } else {
                    $pagePlugins->set('s', 'REMOVE_LINK', 'javascript://');
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

// added language vars
$page->set('s', 'LANG_UPLOAD', i18n('Upload a new plugin', 'pim'));
$page->set('s', 'LANG_UPLOAD_CHOOSE', i18n('Please choose a plugin package', 'pim'));
$page->set('s', 'LANG_UPLOAD_BUTTON', i18n('Upload plugin package', 'pim'));
$page->set('s', 'LANG_INSTALLED', i18n('Installed Plugins', 'pim'));
$page->set('s', 'LANG_EXTRACTED', i18n('Not installed Plugins', 'pim'));
$page->set('s', 'LANG_EXECUTIONORDERINFO_TITLE', i18n('Execution order', 'pim'));
$page->set('s', 'LANG_EXECUTIONORDERINFO_TEXT', i18n('If you click on following arrows you can manage the execution order of plugins. The first plugin (at first position) will be executed first from CONTENIDO, the last plugin (at last position) will be executed at the end. This can be important if CONTENIDO backend has to load a plugin to activate other functions/plugins. Normally the execution order has no impact.', 'pim'));

// added installed plugins to pim_overview
$page->set('s', 'INSTALLED_PLUGINS', $oItem->count());
$page->set('s', 'PLUGINS_INSTALLED', $pluginsInstalled);
$page->set('s', 'PLUGINS_EXTRACTED', $pluginsExtracted);

$page->render();