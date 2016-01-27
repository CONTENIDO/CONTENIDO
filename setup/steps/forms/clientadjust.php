<?php
/**
 * This file contains the client adjust setup mask.
 *
 * @package    Setup
 * @subpackage Form
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client adjust setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupClientAdjust extends cSetupMask {

    function cSetupClientAdjust($step, $previous, $next) {
        global $db, $cfg, $cfgClient;

        cSetupMask::cSetupMask("templates/setup/forms/pathinfo.tpl", $step);
        $this->setHeader(i18n("Client Settings", "setup"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("Client Settings", "setup"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please check the directories identified by the system. If you need to change a client path, click on the name and enter your new path in the available input box.", "setup"));

        $db = getSetupMySQLDBConnection();

        $aClients = listClients($db, $cfg['tab']['clients']);

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();
        $cHTMLFoldableErrorMessages = array();

        $aPathList = array();

        list($a_root_path, $a_root_http_path) = getSystemDirectories();

        @include($cfg['path']['contenido_config'] . 'config.php');

        setupInitializeCfgClient();

        foreach ($aClients as $idclient => $aInfo) {
            $name = $aInfo['name'];

            if (isset($cfgClient[$idclient])) {
                $htmlPath = $cfgClient[$idclient]["path"]["htmlpath"];
                $frontendPath = $cfgClient[$idclient]["path"]["frontend"];
            } else {
                $htmlPath = '';
                $frontendPath = '';
            }

            if ($_SESSION["frontendpath"][$idclient] == "") {
                $iDifferencePos = findSimilarText($cfg['path']['frontend'] . "/", $frontendPath);
                if ($iDifferencePos > 0) {
                    $sClientPath = $a_root_path . "/" . substr($frontendPath, $iDifferencePos + 1, strlen($frontendPath) - $iDifferencePos);
                    $_SESSION["frontendpath"][$idclient] = $sClientPath;
                } else {
                    $_SESSION["frontendpath"][$idclient] = $frontendPath;
                }
            }

            if ($_SESSION["htmlpath"][$idclient] == "") {
                // Use frontendpath instead of htmlpath as the directories should be aligned pairwhise
                $iDifferencePos = findSimilarText($cfg['path']['frontend'] . "/", $frontendPath);
                if ($iDifferencePos > 0) {
                    $sClientPath = $a_root_http_path . "/" . substr($frontendPath, $iDifferencePos + 1, strlen($frontendPath) - $iDifferencePos);
                    $_SESSION["htmlpath"][$idclient] = $sClientPath;
                } else {
                    $_SESSION["htmlpath"][$idclient] = $htmlPath;
                }
            }

            $sName = sprintf(i18n("Old server path for %s (%s)", "setup"), $name, $idclient);
            $sName .= ":<br>" . $frontendPath . "<br><br>";
            $sName .= sprintf(i18n("New server path for %s (%s)", "setup"), $name, $idclient);
            $sName .= ":<br>";
            $oSystemPathBox = new cHTMLTextbox("frontendpath[$idclient]", $_SESSION["frontendpath"][$idclient]);
            $oSystemPathBox->setWidth(100);
            $oSystemPathBox->setClass("small");
            $oClientSystemPath = new cHTMLInfoMessage(array($sName, $oSystemPathBox), "&nbsp;");
            $oClientSystemPath->_oTitle->setStyle("padding-left:8px;padding-bottom:8px;width:90%;");

            $aPathList[] = $oClientSystemPath;

            $sName = sprintf(i18n("Old web path for %s (%s)", "setup"), $name, $idclient);
            $sName .= ":<br>" . $htmlPath . "<br><br>";
            $sName .= sprintf(i18n("New web path for %s (%s)", "setup"), $name, $idclient);
            $sName .= ":<br>";
            $oSystemPathBox = new cHTMLTextbox("htmlpath[$idclient]", $_SESSION["htmlpath"][$idclient]);
            $oSystemPathBox->setWidth(100);
            $oSystemPathBox->setClass("small");
            $oClientSystemPath = new cHTMLInfoMessage(array($sName, $oSystemPathBox), "&nbsp;");
            $oClientSystemPath->_oTitle->setStyle("padding-left:8px;padding-bottom:8px;width:90%;");

            $aPathList[] = $oClientSystemPath;
        }

        $cHTMLErrorMessageList->setContent($aPathList);

        $this->_oStepTemplate->set("s", "CONTROL_PATHINFO", $cHTMLErrorMessageList->render());

        $this->setNavigation($previous, $next);
    }

}

?>