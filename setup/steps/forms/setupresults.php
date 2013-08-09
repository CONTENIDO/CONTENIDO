<?php
/**
 * This file contains the setup results setup mask.
 *
 * @package    Setup
 * @subpackage Form
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
 * Setup results setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupResults extends cSetupMask
{
    function cSetupResults($step)
    {
        $this->setHeader(i18n("Results", "setup"));

        if (!isset($_SESSION["install_failedchunks"]) && !isset($_SESSION["install_failedupgradetable"]) && !isset($_SESSION["configsavefailed"]))
        {
            cSetupMask::cSetupMask("templates/setup/forms/setupresults.tpl", $step);
            $this->_oStepTemplate->set("s", "TITLE", i18n("Results", "setup"));
            $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("CONTENIDO was installed and configured successfully on your server.", "setup"));
            if ($_SESSION["setuptype"] == 'setup') {
                $this->_oStepTemplate->set("s", "LOGIN_INFO", '<p>'.i18n("Please use username <b>sysadmin</b> and the configured password to login into CONTENIDO Backend.", "setup").'</p>');
            } else {
                $this->_oStepTemplate->set("s", "LOGIN_INFO", '');
            }
            $this->_oStepTemplate->set("s", "CHOOSENEXTSTEP", i18n("Please choose an item to start working:", "setup"));
            $this->_oStepTemplate->set("s", "FINISHTEXT", i18n("You can now start using CONTENIDO. Please delete the folder named 'setup'!", "setup"));

            list($rootPath, $rootHttpPath) = getSystemDirectories();

            $cHTMLButtonLink = new cHTMLButtonLink($rootHttpPath . "/contenido/", "Backend - CMS");
            $this->_oStepTemplate->set("s", "BACKEND", $cHTMLButtonLink->render());

            if ($_SESSION["setuptype"] == "setup" && $_SESSION["clientmode"] == "CLIENTEXAMPLES") {
                $cHTMLButtonLink = new cHTMLButtonLink($rootHttpPath . "/cms/", "Frontend - Web");
                $this->_oStepTemplate->set("s", "FRONTEND", $cHTMLButtonLink->render());
            } else {
                $this->_oStepTemplate->set("s", "FRONTEND", "");
            }

            $cHTMLButtonLink = new cHTMLButtonLink("http://www.contenido.org/", "CONTENIDO Website");
            $this->_oStepTemplate->set("s", "WEBSITE", $cHTMLButtonLink->render());

            $cHTMLButtonLink = new cHTMLButtonLink("http://forum.contenido.org/", "CONTENIDO Forum");
            $this->_oStepTemplate->set("s", "FORUM", $cHTMLButtonLink->render());

            $cHTMLButtonLink = new cHTMLButtonLink("http://faq.contenido.org/", "CONTENIDO FAQ");
            $this->_oStepTemplate->set("s", "FAQ", $cHTMLButtonLink->render());
        } else {
            cSetupMask::cSetupMask("templates/setup/forms/setupresultsfail.tpl", $step);
            $this->_oStepTemplate->set("s", "TITLE", i18n("Setup Results", "setup"));

            $this->_oStepTemplate->set("s", "DESCRIPTION", sprintf(i18n("An error occured during installation. Please take a look at the file %s (located in &quot;data/logs/&quot;) for more information.", "setup"), 'setuplog.txt'));

            switch ($_SESSION["setuptype"]) {
                case "setup":
                    $this->setNavigation("setup1", "");
                    break;
                case "upgrade":
                    $this->setNavigation("upgrade1", "");
                    break;
            }
        }
    }
}

?>