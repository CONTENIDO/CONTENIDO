<?php
/**
 * This file contains the installer setup mask.
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
 * Installer setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupInstaller extends cSetupMask
{

    public function __construct($step) {
        cSetupMask::__construct("templates/setup/forms/installer.tpl", $step);

        $this->_stepTemplateClass->set("s", "IFRAMEVISIBILITY", (CON_SETUP_DEBUG) ? 'visible' : 'hidden');
        $this->_stepTemplateClass->set("s", "DBUPDATESCRIPT", "index.php?c=db");

        switch ($_SESSION["setuptype"]) {
            case "setup":
                $this->setHeader(i18n("System Installation", "setup"));
                $this->_stepTemplateClass->set("s", "TITLE", i18n("System Installation", "setup"));
                $this->_stepTemplateClass->set("s", "DESCRIPTION", i18n("CONTENIDO will be installed, please wait. This process may take several moments!", "setup"));
                $this->_stepTemplateClass->set("s", "DONEINSTALLATION", i18n("Setup completed installing. Click on next to continue.", "setup"));
                $this->_stepTemplateClass->set("s", "DESCRIPTION", i18n("Setup is installing, please wait...", "setup"));
                $_SESSION["upgrade_nextstep"] = "setup8";
                $this->setNavigation("", "setup8");
                break;
            case "upgrade":
                $this->setHeader(i18n("System Upgrade", "setup"));
                $this->_stepTemplateClass->set("s", "TITLE", i18n("System Upgrade", "setup"));
                $this->_stepTemplateClass->set("s", "DESCRIPTION", i18n("CONTENIDO will be upgraded, please wait. This process may take several moments!", "setup"));
                $this->_stepTemplateClass->set("s", "DONEINSTALLATION", i18n("Setup completed upgrading. Click on next to continue.", "setup"));
                $this->_stepTemplateClass->set("s", "DESCRIPTION", i18n("Setup is upgrading, please wait...", "setup"));
                $_SESSION["upgrade_nextstep"] = "ugprade6";
                $this->setNavigation("", "upgrade6");
                break;
        }
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $step
     */
    public function cSetupInstaller($step) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($step);
    }
}

?>