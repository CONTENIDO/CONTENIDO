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

// OLD
$plugin = new Contenido_Plugin_Base($db, $cfg, $cfgClient, $lang);

// TODO: Change!
$view = new Contenido_PluginView($sess);

$viewAction = isset($_REQUEST['pim_view'])? $_REQUEST['pim_view'] : 'overview';

switch ($viewAction) {
    case 'update':
        $setup->uninstall($_POST['pluginId']);
        installationRoutine();
        break;
    case 'uninstall':
        $setup->uninstall($_GET['pluginId']);
        break;
    case 'install':
        installationRoutine();
        break;
}

// TODO: Ggfls. koennen einige der Aufrufe auch in die Klasse ausgegliedert und
// uebersichtlicher
// implementiert werden
function installationRoutine() {
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
        throw new cException('You have to installed CONTENIDO ' . $tempXml->general->min_contenido_version . ' or higher to install this plugin!');
    }

    // check max contenido version
    if (!empty($tempXml->general->max_contenido_version) && version_compare($cfg['version'], $tempXml->general->max_contenido_version, '>')) {
        $extractor->destroyTempFiles();
        throw new cException('You\'re current CONTENIDO version is to new - max CONTENIDO version: ' . $tempXml->general->max_contenido_version);
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

    // Success message
    $notice = i18n('The plugin has been successfully installed', 'pim');

    // close extracted archive
    $extractor->closeArchive();

    // delete temporary files
    $extractor->destroyTempFiles();
}

// get all installed plugins
$oItem = new PimPluginCollection();
$oItem->select();

while (($plugin = $oItem->next()) !== false) {

    // initalization new class
    $view2 = new Contenido_PluginView($sess);

    // date
    $date = date_format(date_create($plugin->get('installed')), i18n('Y-m-d', 'pim'));

    $view2->setVariable($plugin->get('idplugin'), 'IDPLUGIN');
    $view2->setVariable($plugin->get('name'), 'NAME');
    $view2->setVariable($plugin->get('version'), 'VERSION');
    $view2->setVariable($plugin->get('description'), 'DESCRIPTION');
    $view2->setVariable($plugin->get('author'), 'AUTHOR');
    $view2->setVariable($plugin->get('mail'), 'MAIL');
    $view2->setVariable($plugin->get('website'), 'WEBSITE');
    $view2->setVariable($plugin->get('copyright'), 'COPYRIGHT');
    $view2->setVariable($date, 'INSTALLED');
    $view2->setVariable($notice, 'NOTICE');

    $view2->setVariable(i18n('Installed since', 'pim'), 'LANG_INSTALLED');
    $view2->setVariable(i18n('Author', 'pim'), 'LANG_AUTHOR');
    $view2->setVariable(i18n('Contact', 'pim'), 'LANG_CONTACT');
    $view2->setVariable(i18n('Uninstall', 'pim'), 'LANG_UNINSTALL');
    $view2->setVariable(i18n('Update', 'pim'), 'LANG_UPDATE');
    $view2->setVariable(i18n('Please choose your new file', 'pim'), 'LANG_UPDATE_CHOOSE');
    $view2->setVariable(i18n('Update', 'pim'), 'LANG_UPDATE_UPLOAD');

    // TODO: Implementierung einer Abfangmeldung "Wollen Sie dieses Plugin wirklich löschen?"
    // uninstall link
    $view2->setVariable($sess->url('main.php?area=pim&frame=4&pim_view=uninstall&pluginId=' . $plugin->get('idplugin')), 'UNINSTALL_LINK');

    $view2->setTemplate('plugins/pim/templates/pim_plugins.html');
    $plugins .= $view2->getRendered(1); // rendered only pim_plugins
}

// added language vars
$view->setVariable(i18n('Add new plugin', 'pim'), 'LANG_ADD');
$view->setVariable(i18n('Please choose a plugin package', 'pim'), 'LANG_ADD_CHOOSE');
$view->setVariable(i18n('Upload plugin package', 'pim'), 'LANG_ADD_UPLOAD');
$view->setVariable(i18n('Installed Plugins', 'pim'), 'LANG_INSTALLED');

// added installed plugins to pim_overview
$view->setVariable($oItem->count(), 'INSTALLED_PLUGINS');
$view->setVariable($plugins, 'PLUGINS');

// show overview page
$view->setTemplate('plugins/pim/templates/pim_overview.html');

$view->getRendered();
