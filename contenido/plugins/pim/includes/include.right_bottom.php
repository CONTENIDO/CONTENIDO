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

// get all installed plugins
$oItem = new PimPluginCollection();
$oItem->select();

while (($plugin = $oItem->next()) !== false) {

    // initalization new class
    $pagePlugins = new cGuiPage('pim_plugins', 'pim');

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
    $pagePlugins->set('s', 'UNINSTALL_LINK', $sess->url('main.php?area=pim&frame=4&pim_view=uninstall&pluginId=' . $plugin->get('idplugin')));

    $plugins .= $pagePlugins->render(null, true);
}

// added language vars
$page->set('s', 'LANG_ADD', i18n('Add new plugin', 'pim'));
$page->set('s', 'LANG_ADD_CHOOSE', i18n('Please choose a plugin package', 'pim'));
$page->set('s', 'LANG_ADD_UPLOAD', i18n('Upload plugin package', 'pim'));
$page->set('s', 'LANG_INSTALLED', i18n('Installed Plugins', 'pim'));

// added installed plugins to pim_overview
$page->set('s', 'INSTALLED_PLUGINS', $oItem->count());
$page->set('s', 'PLUGINS', $plugins);

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
}

// TODO: Ggfls. koennen einige der Aufrufe auch in die Klasse ausgegliedert und
// uebersichtlicher
// implementiert werden
function installationRoutine($page) {
    global $cfg, $setup;

    // name of uploaded file
    $tempFile = cSecurity::escapeString($_FILES['package']['name']);

    // path to temp-dir
    $tempFilePath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

    move_uploaded_file($_FILES['package']['tmp_name'], $tempFilePath . $tempFile);

    // initalizing plugin archive extractor
    try {
        $extractor = new PimPluginArchiveExtractor($tempFilePath, $tempFile);
        $setup->addArchiveObject($extractor);
    } catch (cException $e) {
        $extractor->destroyTempFiles();
    }

    // xml file validation
    $setup->tempXml = $extractor->extractArchiveFileToVariable('plugin.xml');
    $setup->checkXml();

    // load plugin.xml to an xml-string
    $tempXml = simplexml_load_string($setup->tempXml);

    // check min contenido version
    if (!empty($tempXml->general->min_contenido_version) && version_compare($cfg['version'], $tempXml->general->min_contenido_version, '<')) {
        $extractor->destroyTempFiles();
        $page->displayError(i18n('You have to install CONTENIDO <strong>', 'pim') . $tempXml->general->min_contenido_version . i18n('</strong> or higher to install this plugin!', 'pim'));
        $page->render();
        exit();
    }

    // check max contenido version
    if (!empty($tempXml->general->max_contenido_version) && version_compare($cfg['version'], $tempXml->general->max_contenido_version, '>')) {
        $extractor->destroyTempFiles();
        $page->displayError(i18n('You\'re current CONTENIDO version is to new - max CONTENIDO version: ' . $tempXml->general->max_contenido_version . '', 'pim'));
        $page->render();
        exit();
    }

    // build the new plugin dir
    $tempPluginDir = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $tempXml->general->plugin_foldername . DIRECTORY_SEPARATOR;

    // extract files into plugin dir
    if ($setup->valid === true) {
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

    // close extracted archive
    $extractor->closeArchive();

    // delete temporary files
    $extractor->destroyTempFiles();
}

$page->render();