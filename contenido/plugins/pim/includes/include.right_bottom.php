<?php

/**
 * This file contains Plugin Manager Backend View.
 *
 * @package CONTENIDO Plugins
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

$setup = new PimPluginSetup();

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
    case 'activestatus':
        $setup->changeActiveStatus($_GET['pluginId'], $page);
        break;
    case 'update':
        $setup->checkZip();
        $setup->checkSamePlugin($_POST['pluginId']);
        $setup->uninstall($_POST['pluginId']);
        installationRoutine($page, true, $_POST['foldername'], true);
        break;
    case 'uninstall':
        $setup->uninstall($_GET['pluginId'], $page);
        break;
    case 'uninstall-extracted':
        $setup->uninstallDir($_GET['pluginFoldername'], $page);
        break;
    case 'install':
        $setup->checkZip();
        $setup->checkSamePlugin();
        installationRoutine($page);
        break;
    case 'install-extracted':
        installationRoutine($page, true, $_GET['pluginFoldername']);
        break;
}

// TODO: Move the following function into classes
/**
 * Installation steps to install a new plugin.
 * Function differ between extracted
 * and Zip archive plugins
 *
 * @param cGuiPage $page
 * @param boolean $isExtracted
 * @param string $extractedPath foldername from extracted plugin
 * @param boolean $update
 */
function installationRoutine($page, $isExtracted = false, $extractedPath = '', $update = false) {
    global $setup;
    $cfg = cRegistry::getConfig();
    $sess = cRegistry::getSession();

    if ($isExtracted === false) {

        // name of uploaded file
        $tempFileName = cSecurity::escapeString($_FILES['package']['name']);

        // path to temporary dir
        $tempFileNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

        // move temporary files into CONTENIDO temp dir
        move_uploaded_file($_FILES['package']['tmp_name'], $tempFileNewPath . $tempFileName);

        // initalizing plugin archive extractor
        try {
            $extractor = new PimPluginArchiveExtractor($tempFileNewPath, $tempFileName);
            $setup->addArchiveObject($extractor);
        } catch (cException $e) {
            $extractor->destroyTempFiles();
        }

        $tempPluginXmlContent = $extractor->extractArchiveFileToVariable('plugin.xml');
        $setup->setTempXml($tempPluginXmlContent);
    } else {
        $tempPluginXmlContent = file_get_contents($cfg['path']['contenido'] . $cfg['path']['plugins'] . $extractedPath . '/plugin.xml');
        $setup->setTempXml($tempPluginXmlContent);
        $setup->setIsExtracted($isExtracted);
        $setup->setExtractedPath($extractedPath);
    }

    // xml file validation
    $setup->checkValidXml();

    // load plugin.xml to an xml-string
    $tempXml = simplexml_load_string($setup->getTempXml());

    // check min CONTENIDO version
    if ($tempXml->general->min_contenido_version != '' && version_compare($cfg['version'], $tempXml->general->min_contenido_version, '<')) {

        if ($isExtracted === false) {
            $extractor->destroyTempFiles();
        }

        $pageError = new cGuiPage('pim_error', 'pim');
        $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
        $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
        $pageError->displayError(i18n('You have to install CONTENIDO <strong>', 'pim') . $tempXml->general->min_contenido_version . i18n('</strong> or higher to install this plugin!', 'pim'));
        $pageError->render();
        exit();
    }

    // check max CONTENIDO version
    if ($tempXml->general->max_contenido_version != '' && version_compare($cfg['version'], $tempXml->general->max_contenido_version, '>')) {

        if ($isExtracted === false) {
            $extractor->destroyTempFiles();
        }

        $pageError = new cGuiPage('pim_error', 'pim');
        $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
        $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
        $pageError->displayError(i18n('Your current CONTENIDO version is to new - max CONTENIDO version: ' . $tempXml->general->max_contenido_version . '', 'pim'));
        $pageError->render();
        exit();
    }

    // build the new plugin dir
    $tempPluginDir = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $tempXml->general->plugin_foldername . DIRECTORY_SEPARATOR;

    // extract files into plugin dir
    if ($setup->getValid() === true && $isExtracted === false) {
        try {
            $extractor->setDestinationPath($tempPluginDir);
        } catch (cException $e) {
            $extractor->destroyTempFiles();
        }

        try {
            $extractor->extractArchive();
        } catch (cException $e) {
            $extractor->destroyTempFiles();
        }
    }

    if ($isExtracted === true) {

        // sql inserts
        $setup->install($tempXml);

        // success message
        if ($update == false) {
            $page->displayInfo(i18n('The plugin has been successfully installed. To apply the changes please login into backend again.', 'pim'));
        } else {
            $page->displayInfo(i18n('The plugin has been successfully updated. To apply the changes please login into backend again.', 'pim'));
        }
    } else {

        // success message
        $page->displayInfo(i18n('The plugin has been successfully uploaded. Now you can install it.', 'pim'));

        // close extracted archive
        $extractor->closeArchive();
    }
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
$handle = opendir($cfg['path']['plugins']);
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