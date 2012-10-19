<?php
/**
 * Plugin Manager Backend View
 *
 * @package plugin
 * @subpackage Plugin Manager
 * @version SVN Revision $Rev:$
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// initializing classes
$setup = new PimPluginSetup();
$page = new cGuiPage('pim_overview', 'pim');

// check disable plugin var
if($cfg['debug']['disable_plugins'] === true) {
    $page->displayWarning(i18n('Currently the plugin system is disabled via configuration', 'pim'));
}

$viewAction = isset($_REQUEST['pim_view'])? $_REQUEST['pim_view'] : 'overview';

switch ($viewAction) {
    case 'update':
        $setup->uninstall($_POST['pluginId']);
        installationRoutine($page);
        break;
    case 'uninstall':
        $setup->uninstall($_GET['pluginId'], $page);
        break;
    case 'install':
        installationRoutine($page);
        break;
    case 'install-extracted':
        installationRoutine($page, true, $_GET['pluginFoldername']);
        break;
}

// TODO: Ggfls. koennen einige der Aufrufe auch in die Klasse ausgegliedert und
// uebersichtlicher
// implementiert werden
function installationRoutine($page, $isExtracted = false, $extractedPath = '') {
    global $setup;
    $cfg = cRegistry::getConfig();
    $sess = cRegistry::getSession();

    if ($isExtracted === false) {

        // name of uploaded file
        $tempFileName = cSecurity::escapeString($_FILES['package']['name']);

        // path to temp-dir
        $tempFileNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

        move_uploaded_file($_FILES['package']['tmp_name'], $tempFileNewPath . $tempFileName);

        // initalizing plugin archive extractor
        try {
            $extractor = new PimPluginArchiveExtractor($tempFileNewPath, $tempFileName);
            $setup->addArchiveObject($extractor);
        } catch (cException $e) {
            $extractor->destroyTempFiles();
        }

        $setup->tempXml = $extractor->extractArchiveFileToVariable('plugin.xml');
    } else {
        $setup->isExtracted = true;
        $setup->extractedPath = $extractedPath;
        $setup->tempXml = file_get_contents($cfg['path']['contenido'] . $cfg['path']['plugins'] . $extractedPath . '/plugin.xml');
    }

    // xml file validation
    $setup->checkXml();

    // load plugin.xml to an xml-string
    $tempXml = simplexml_load_string($setup->tempXml);

    // check min CONTENIDO version
    if (!empty($tempXml->general->min_contenido_version) && version_compare($cfg['version'], $tempXml->general->min_contenido_version, '<')) {

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
    if (!empty($tempXml->general->max_contenido_version) && version_compare($cfg['version'], $tempXml->general->max_contenido_version, '>')) {

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
    if ($setup->valid === true && $isExtracted === false) {
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

    // sql inserts
    $setup->install($tempXml);

    // success message
    $page->displayInfo(i18n('The plugin has been successfully installed', 'pim'));

    if ($isExtracted === false) {

        // close extracted archive
        $extractor->closeArchive();

        // delete temporary files
        $extractor->destroyTempFiles();
    } else {

        $tempPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $extractedPath;

        // remove plugin.xml if exists
        if (cFileHandler::exists($tempPath . '/plugin.xml')) {
            cFileHandler::remove($tempPath . '/plugin.xml');
        }

        // remove plugin.sql if exists
        if (cFileHandler::exists($tempPath . '/plugin.sql')) {
            cFileHandler::remove($tempPath . '/plugin.sql');
        }
    }
}

// initializing array for installed plugins
$installedPluginFoldernames = array();

// get all installed plugins
$oItem = new PimPluginCollection();
$oItem->select();

while (($plugin = $oItem->next()) !== false) {

    // initalization new class
    $pagePlugins = new cGuiPage('pim_plugins_installed', 'pim');

    // date
    $date = date_format(date_create($plugin->get('installed')), i18n('Y-m-d', 'pim'));

    $pagePlugins->set('s', 'IDPLUGIN', $plugin->get('idplugin'));
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
    $pagePlugins->set('s', 'LANG_UNINSTALL', i18n('Uninstall', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE', i18n('Update', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE_CHOOSE', i18n('Please choose your new file', 'pim'));
    $pagePlugins->set('s', 'LANG_UPDATE_UPLOAD', i18n('Update', 'pim'));

    // TODO: Implementierung einer Abfangmeldung "Wollen Sie dieses Plugin
    // wirklich löschen?"
    // uninstall link
    if (is_writable($cfg['path']['contenido'] . $cfg['path']['plugins']  . $plugin->get('folder'))) {
        $pagePlugins->set('s', 'UNINSTALL_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=uninstall&pluginId=' . $plugin->get('idplugin')));
        $pagePlugins->set('s', 'LANG_WRITABLE', '');
    } else {
        $pagePlugins->set('s', 'UNINSTALL_LINK', $sess->url('main.php?area=pim&frame=4'));
        $pagePlugins->set('s', 'LANG_WRITABLE', i18n('(<span style="color: red;">This plugin is not writeable, please set the rights manually</span>)', 'pim'));
    }

    // put foldername into array installedPluginFoldernames
    $installedPluginFoldernames[] = $plugin->get('folder');

    $pluginsInstalled .= $pagePlugins->render(null, true);
}

// get extracted plugins
$handle = opendir($cfg['path']['plugins']);
while ($pluginFoldername = readdir($handle)) {

    $tempPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $pluginFoldername . '/plugin.xml';

    if (cFileHandler::exists($tempPath) && !in_array($pluginFoldername, $installedPluginFoldernames)) {

        // initalization new class
        $pagePlugins = new cGuiPage('pim_plugins_extracted', 'pim');

        $pagePlugins->set('s', 'LANG_FOLDERNAME', i18n('Foldername', 'pim'));
        $pagePlugins->set('s', 'FOLDERNAME', $pluginFoldername);
        $pagePlugins->set('s', 'INSTALL_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=install-extracted&pluginFoldername=' . $pluginFoldername));

        $pluginsExtracted .= $pagePlugins->render(null, true);
    }
}

// if pluginsExtracted var is empty
if (empty($pluginsExtracted)) {
    $pluginsExtracted = i18n('No entries', 'pim');
}

// added language vars
$page->set('s', 'LANG_ADD', i18n('Add new plugin', 'pim'));
$page->set('s', 'LANG_ADD_CHOOSE', i18n('Please choose a plugin package', 'pim'));
$page->set('s', 'LANG_ADD_UPLOAD', i18n('Upload plugin package', 'pim'));
$page->set('s', 'LANG_INSTALLED', i18n('Installed Plugins', 'pim'));
$page->set('s', 'LANG_EXTRACTED', i18n('Not installed Plugins', 'pim'));

// added installed plugins to pim_overview
$page->set('s', 'INSTALLED_PLUGINS', $oItem->count());
$page->set('s', 'PLUGINS_INSTALLED', $pluginsInstalled);
$page->set('s', 'PLUGINS_EXTRACTED', $pluginsExtracted);

$page->render();