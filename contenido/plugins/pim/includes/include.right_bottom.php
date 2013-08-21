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

// NEW Initializing PimPluginSetup class
$setup = new PimPluginSetup();
$setup->_setPageClass($page);

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

$viewAction = isset($_REQUEST['pim_view'])? $_REQUEST['pim_view'] : 'overview';

switch ($viewAction) {
    case 'activestatus': // NEW
        $status = new PimPluginSetupStatus();
        $status->changeActiveStatus($_GET['pluginId']);
        break;
    case 'update': // DEV
        plugin_include('pim', 'classes/setup/class.pimpluginsetup.php');
        unset($setup);
        $setup = new PimPluginSetupOld();
        $setup->checkZip();
        $setup->checkSamePlugin($_POST['pluginId']);
        $setup->setIsUpdate(1);
        $setup->uninstall($_POST['pluginId']);
        installationRoutine($page, true, $_POST['foldername'], true);
        break;
    case 'uninstall': // NEW
        $delete = new PimPluginSetupUninstall();
        $setup->setMode('uninstall');
        $setup::_setPluginId($_GET['pluginId']);
        $delete->uninstall();
        break;
    case 'uninstall-extracted': // NEW
        $delete = new PimPluginSetupUninstall();
        $setup->setMode('uninstall');
        $delete->_setPluginFoldername($_GET['pluginFoldername']);
        $delete->uninstallDir();
        break;
    case 'install': // NEW
        $setup->setMode('uploaded');
        $setup->checkXml();
        $new = new PimPluginSetupInstall();
        $new->install();
        break;
    case 'install-extracted': // NEW
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
$oItem->select(null, null, 'name');

while (($plugin = $oItem->next()) !== false) {

    // initialization new template class
    $pagePlugins = new cTemplate();

    // date
    $date = date_format(date_create($plugin->get('installed')), i18n('Y-m-d', 'pim'));

    $pagePlugins->set('s', 'IDPLUGIN', $plugin->get('idplugin'));
    $pagePlugins->set('s', 'FOLDERNAME', $plugin->get('folder'));
    $pagePlugins->set('s', 'NAME', $plugin->get('name'));
    $pagePlugins->set('s', 'VERSION', $plugin->get('version'));
    $pagePlugins->set('s', 'DESCRIPTION', $plugin->get('description'));
    $pagePlugins->set('s', 'AUTHOR', $plugin->get('author'));
    $pagePlugins->set('s', 'MAIL', $plugin->get('mail'));
    $pagePlugins->set('s', 'WEBSITE', $plugin->get('website'));
    $pagePlugins->set('s', 'COPYRIGHT', $plugin->get('copyright'));
    $pagePlugins->set('s', 'INSTALLED', $date);

    $pagePlugins->set('s', 'LANG_INSTALLED', i18n('Installed since', 'pim'));
    $pagePlugins->set('s', 'LANG_AUTHOR', i18n('Author', 'pim'));
    $pagePlugins->set('s', 'LANG_CONTACT', i18n('Contact', 'pim'));

    $pagePlugins->set('s', 'LANG_UPDATE', i18n('Update', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE_CHOOSE', i18n('Please choose your new file', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE_UPLOAD', i18n('Update', 'pim'));

    // enable / disable functionality
    $activeStatus = $plugin->get('active');
    $tempActiveStatusLink = $sess->url('main.php?area=pim&frame=4&pim_view=activestatus&pluginId=' . $plugin->get('idplugin'));
    if ($activeStatus == 1) {
        $pagePlugins->set('s', 'LANG_ACTIVESTATUS', '<img src="images/online.gif" class="vAlignMiddle" /> <a href="' . $tempActiveStatusLink . '">' . i18n('Disable plugin', 'pim') . '</a>');
    } else {
        $pagePlugins->set('s', 'LANG_ACTIVESTATUS', '<img src="images/offline.gif" class="vAlignMiddle" /> <a href="' . $tempActiveStatusLink . '">' . i18n('Enable plugin', 'pim') . '</a>');
    }

    // uninstall link
    $tempUninstallLink = $sess->url('main.php?area=pim&frame=4&pim_view=uninstall&pluginId=' . $plugin->get('idplugin'));
    $pagePlugins->set('s', 'UNINSTALL_LINK', '<a href="javascript:void(0)" onclick="javascript:showConfirmation(\'' . i18n('Are you sure to delete this plugin? Files are not deleted in filesystem.', 'pim') . '\', function() { window.location.href=\'' . $tempUninstallLink . '\';})">' . i18n('Uninstall', 'pim') . '</a>');

    // put foldername into array installedPluginFoldernames
    $installedPluginFoldernames[] = $plugin->get('folder');

    $pluginsInstalled .= $pagePlugins->generate($tempTplPath . '/template.pim_plugins_installed.html', true, false);
}

// get extracted plugins
if (is_dir($cfg['path']['plugins'])) {
    if ($handle = opendir($cfg['path']['plugins'])) {
        while ($pluginFoldername = readdir($handle)) {

            $tempPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $pluginFoldername . '/plugin.xml';

            if (cFileHandler::exists($tempPath) && !in_array($pluginFoldername, $installedPluginFoldernames)) {

                // initalization new template class
                $pagePlugins = new cTemplate();

                $pagePlugins->set('s', 'LANG_FOLDERNAME', i18n('Foldername', 'pim'));
                $pagePlugins->set('s', 'LANG_TOOLTIP_INSTALL', i18n('Install extracted plugin', 'pim'));
                $pagePlugins->set('s', 'LANG_TOOLTIP_UNINSTALL', i18n('Uninstall extracted plugin (deleted plugin files from filesystem)', 'pim'));
                $pagePlugins->set('s', 'FOLDERNAME', $pluginFoldername);
                $pagePlugins->set('s', 'INSTALL_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=install-extracted&pluginFoldername=' . $pluginFoldername));

                // uninstall link
                if (is_writable($cfg['path']['contenido'] . $cfg['path']['plugins'] . $pluginFoldername)) {
                    $pagePlugins->set('s', 'UNINSTALL_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=uninstall-extracted&pluginFoldername=' . $pluginFoldername));
                    $pagePlugins->set('s', 'LANG_WRITABLE', '');
                } else {
                    $pagePlugins->set('s', 'UNINSTALL_LINK', 'javascript://');
                    $pagePlugins->set('s', 'LANG_WRITABLE', '(<span class="settingWrong">' . i18n('This plugin is not writeable, please set the rights manually', 'pim') . '</span>)');
                }

                $pluginsExtracted .= $pagePlugins->generate($tempTplPath . '/template.pim_plugins_extracted.html', true, false);
            }
        }
        closedir($handle);
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

// added installed plugins to pim_overview
$page->set('s', 'INSTALLED_PLUGINS', $oItem->count());
$page->set('s', 'PLUGINS_INSTALLED', $pluginsInstalled);
$page->set('s', 'PLUGINS_EXTRACTED', $pluginsExtracted);

$page->render();