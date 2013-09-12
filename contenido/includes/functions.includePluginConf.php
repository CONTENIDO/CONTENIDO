<?php
/**
 * Plugin include function
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$plugins = array();
$pluginFolder = cRegistry::getBackendPath() . $cfg['path']['plugins'];

if ($cfg['debug']['disable_plugins'] === false) {
    // Initialize plugin manager
    i18nRegisterDomain('pim', $pluginFolder . 'pim/locale/');
    include_once($pluginFolder . 'pim/includes/config.plugin.php');

    // Load all active plugins
    $pluginColl = new PimPluginCollection();
    $pluginColl->setWhere('active', 1);
    $pluginColl->setOrder('executionorder ASC');
    $pluginColl->query();

    while (($plugin = $pluginColl->next()) !== false) {
        $pluginName = $plugin->get('folder');

        if (is_dir($pluginFolder . $pluginName . '/')) {
            $plugins[] = $pluginName;
        }
    }
}

// Include all active plugins
foreach ($plugins as $pluginName) {
    $pluginLocaleDir = $pluginFolder . $pluginName . '/locale/';
    $pluginConfigFile = $pluginFolder . $pluginName . '/includes/config.plugin.php';

    if (cFileHandler::exists($pluginLocaleDir)) {
        i18nRegisterDomain($pluginName, $pluginLocaleDir);
    }

    if (cFileHandler::exists($pluginConfigFile)) {
        include_once($pluginConfigFile);
    }
}

// Load legacy plugins frontendusers and frontendlogic
// They remain in old sub plugins logic for now
scanPlugins("frontendusers");
scanPlugins("frontendlogic");