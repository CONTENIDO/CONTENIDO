<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: clientadjust.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cSetupClientAdjust extends cSetupMask {

    function cSetupClientAdjust($step, $previous, $next) {
        global $cfg;

        $cfg["tab"]["sequence"] = $_SESSION["dbprefix"]."_sequence";

        cSetupMask::cSetupMask("templates/setup/forms/pathinfo.tpl", $step);
        $this->setHeader(i18n("Client Settings"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("Client Settings"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please check the directories identified by the system. If you need to change a client path, click on the name and enter your new path in the available input box."));

        $db = new DB_Contenido($_SESSION["dbhost"], $_SESSION["dbname"], $_SESSION["dbuser"], $_SESSION["dbpass"]);

        $aClients = listClients($db, $_SESSION["dbprefix"]."_clients");

        $cHTMLErrorMessageList = new cHTMLErrorMessageList;

        $cHTMLFoldableErrorMessages = array();

        $aPathList = array();

        list($a_root_path, $a_root_http_path) = getSystemDirectories();

        @include($a_root_path . "/contenido/includes/config.php");

        foreach ($aClients as $iIdClient => $aInfo) {
            if (!isset($_SESSION["frontendpath"][$iIdClient]) || $_SESSION["frontendpath"][$iIdClient] == "") {
                $iDifferencePos = findSimilarText($cfg['path']['frontend']."/", $aInfo["frontendpath"]);

                if ($iDifferencePos > 0) {
                    $sClientPath = $a_root_path ."/". substr($aInfo["frontendpath"], $iDifferencePos + 1, strlen($aInfo["frontendpath"]) - $iDifferencePos);

                    $_SESSION["frontendpath"][$iIdClient] = $sClientPath;
                } else {
                    $_SESSION["frontendpath"][$iIdClient] = $aInfo["frontendpath"];
                }
            }

            if (!isset($_SESSION["htmlpath"][$iIdClient]) || $_SESSION["htmlpath"][$iIdClient] == "") {
                // Use frontendpath instead of htmlpath as the directories should be aligned pairwhise
                $iDifferencePos = findSimilarText($cfg['path']['frontend'] . "/", $aInfo["frontendpath"]);

                if ($iDifferencePos > 0) {
                    $sClientPath = $a_root_http_path . "/" . substr($aInfo["frontendpath"], $iDifferencePos + 1, strlen($aInfo["frontendpath"]) - $iDifferencePos);
                    $_SESSION["htmlpath"][$iIdClient] = $sClientPath;
                } else {
                    $_SESSION["htmlpath"][$iIdClient] = $aInfo["htmlpath"];
                }
            }

            $sName = sprintf(i18n("Old server path for %s (%s)"), $aInfo["name"], $iIdClient);
            $sName .= ":<br>" . $aInfo["frontendpath"]."<br><br>";
            $sName .= sprintf(i18n("New server path for %s (%s)"), $aInfo["name"], $iIdClient);
            $sName .= ":<br>";
            $oSystemPathBox = new cHTMLTextbox("frontendpath[$iIdClient]", $_SESSION["frontendpath"][$iIdClient]);
            $oSystemPathBox->setWidth(100);
            $oSystemPathBox->setClass("small");
            $oClientSystemPath = new cHTMLInfoMessage(array($sName, $oSystemPathBox), "&nbsp;");
            $oClientSystemPath->_oTitle->setStyle("padding-left:8px;padding-bottom:8px;width:90%;");

            $aPathList[] = $oClientSystemPath;

            $sName = sprintf(i18n("Old web path for %s (%s)"), $aInfo["name"], $iIdClient);
            $sName .= ":<br>" . $aInfo["htmlpath"]."<br><br>";
            $sName .= sprintf(i18n("New web path for %s (%s)"), $aInfo["name"], $iIdClient);
            $sName .= ":<br>";
            $oSystemPathBox = new cHTMLTextbox("htmlpath[$iIdClient]", $_SESSION["htmlpath"][$iIdClient]);
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