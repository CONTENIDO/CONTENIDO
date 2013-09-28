<?php
/**
 * This file contains various helper functions to deal with the setup process.
 *
 * @package    Setup
 * @subpackage Helper
 * @version    SVN Revision $Rev:$
 *
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Generates the step display.
 *
 * @param   int  iCurrentStep  The current step to display active.
 * @return  string
 */
function cGenerateSetupStepsDisplay($iCurrentStep) {
    if (!defined('CON_SETUP_STEPS')) {
        return '';
    }
    $sStepsPath = '';
    for ($i = 1; $i < CON_SETUP_STEPS + 1; $i++) {
        $sCssActive = '';
        if ($iCurrentStep == $i) {
            $sCssActive = 'active';
        }
        $sStepsPath .= '<span class="' . $sCssActive . '">&nbsp;' . strval($i) . '&nbsp;</span>&nbsp;&nbsp;&nbsp;';
    }
    return $sStepsPath;
}

/**
 * Logs general setup failures into setuplog.txt in logs directory.
 *
 * @param   string  $sErrorMessage  Message to log in file
 * @return  void
 */
function logSetupFailure($sErrorMessage) {
    global $cfg;
    cFileHandler::write($cfg['path']['contenido_logs'] . 'setuplog.txt', $sErrorMessage . PHP_EOL . PHP_EOL, true);
}

/**
 * Initializes clients configuration, if not done before
 * @global  array  $cfg
 * @global  array  $cfgClient
 * @param  bool  $reset  Flag to reset any existing client configuration
 */
function setupInitializeCfgClient($reset = false) {
    global $cfg, $cfgClient;

    if (true === $reset) {
        $cfgClient = array();
    }

    // Load client configuration
    if (empty($cfgClient) || !isset($cfgClient['set'])) {
        if (cFileHandler::exists($cfg['path']['contenido_config'] . 'config.clients.php')) {
            require($cfg['path']['contenido_config'] . 'config.clients.php');
        } else {
            $db = getSetupMySQLDBConnection();

            $db->query("SELECT * FROM " . $cfg["tab"]["clients"]);
            while ($db->nextRecord()) {
                updateClientCache($db->f("idclient"), $db->f("htmlpath"), $db->f("frontendpath"));
            }
        }
    }
}

?>