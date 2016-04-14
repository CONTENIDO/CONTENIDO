<?php
/**
 * This file contains the client mode setup mask.
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
 * Client mode setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupClientMode extends cSetupMask {

    /**
     * cSetupClientMode constructor.
     * @param $step
     * @param $previous
     * @param $next
     */
    public function __construct($step, $previous, $next) {

        cSetupMask::cSetupMask("templates/setup/forms/clientmode.tpl", $step);
        $this->setHeader(i18n("Example Client", "setup"));
        $this->_stepTemplateClass->set("s", "TITLE", i18n("Example Client", "setup"));
        $this->_stepTemplateClass->set("s", "DESCRIPTION", i18n("If you are new to CONTENIDO, you should create an example client to start working with.", "setup"));

        cArray::initializeKey($_SESSION, "clientmode", "");

        $folders = "";
        $moduleFolderNotEmpty = false;
        if (cFileHandler::exists("../cms/css")) {
            if (!cDirHandler::isDirectoryEmpty("../cms/css")) {
                $folders .= "cms/css/, ";
            }
        }
        if (cFileHandler::exists("../cms/js")) {
            if (!cDirHandler::isDirectoryEmpty("../cms/js")) {
                $folders .= "cms/js/, ";
            }
        }
        if (cFileHandler::exists("../cms/templates")) {
            if (!cDirHandler::isDirectoryEmpty("../cms/templates")) {
                $folders .= "cms/templates/, ";
            }
        }
        if (cFileHandler::exists("../cms/data/modules")) {
            if (!cDirHandler::isDirectoryEmpty("../cms/data/modules")) {
                $folders .= "cms/data/modules/, ";
                $moduleFolderNotEmpty = true;
            }
        }
        if (cFileHandler::exists("../cms/data/layouts")) {
            if (!cDirHandler::isDirectoryEmpty("../cms/data/layouts")) {
                $folders .= "cms/data/layouts/, ";
            }
        }

        $this->_stepTemplateClass->set("s", "FOLDER_MESSAGE_EXAMPLES", "");
        $this->_stepTemplateClass->set("s", "FOLDER_MESSAGE_MODULES", "");
        if (strlen($folders) > 0) {
            $folders = substr($folders, 0, strlen($folders) - 2);
        }

        $exampleMessage = i18n("PLEASE NOTE: Some folders (%s) which are used by the example client aren't empty. THESE WILL BE OVERWRITTEN", "setup");
        $moduleMessage = i18n("PLEASE NOTE: The cms/data/modules folder is not empty. IT WILL BE OVERWRITTEN", "setup");

        $aChoices = array(
            "CLIENTEXAMPLES" => i18n("Client with example modules and example content", "setup") . ((strlen($folders) > 0) ? " <span class='additionalInfo'>(" . sprintf($exampleMessage, $folders) . ")</span>" : ""),
            "CLIENTMODULES" => i18n("Client with example modules, but without example content", "setup") . (($moduleFolderNotEmpty) ? " <span class='additionalInfo'>(" . $moduleMessage . ")</span>" : ""),
            "NOCLIENT" => i18n("Don't create client", "setup")
        );

        foreach ($aChoices as $sKey => $sChoice) {
            $oRadio = new cHTMLRadiobutton("clientmode", $sKey);
            $oRadio->setLabelText(" ");
            $oRadio->setStyle('width:auto;border:0;');

            if ($_SESSION["clientmode"] == $sKey || ($_SESSION["clientmode"] == "" && $sKey == "CLIENTEXAMPLES")) {
                $oRadio->setChecked("checked");
            }

            $oLabel = new cHTMLLabel($sChoice, $oRadio->getId());

            $this->_stepTemplateClass->set("s", "CONTROL_" . $sKey, $oRadio->render());
            $this->_stepTemplateClass->set("s", "LABEL_" . $sKey, $oLabel->render());
        }

        $this->setNavigation($previous, $next);
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $step
     * @param $previous
     * @param $next
     */
    function cSetupClientMode($step, $previous, $next) {
        $this->__construct($step, $previous, $next);
    }

}

?>