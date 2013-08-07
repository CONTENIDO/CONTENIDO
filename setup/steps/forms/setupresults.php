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
 *   $Id: setupresults.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cSetupResults extends cSetupMask
{
    function cSetupResults($step)
    {
        $this->setHeader(i18n("Results"));

        if (!isset($_SESSION["install_failedchunks"]) && !isset($_SESSION["install_failedupgradetable"]) && !isset($_SESSION["configsavefailed"]))
        {
            cSetupMask::cSetupMask("templates/setup/forms/setupresults.tpl", $step);
            $this->_oStepTemplate->set("s", "TITLE", i18n("Results"));
            $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Contenido was installed and configured successfully on your server."));
            if ($_SESSION["setuptype"] == 'setup') {
                $this->_oStepTemplate->set("s", "LOGIN_INFO", '<p>'.i18n("Please use username <b>sysadmin</b> and password <b>sysadmin</b> to login into Contenido Backend.").'</p>');
            } else {
                $this->_oStepTemplate->set("s", "LOGIN_INFO", '');
            }
            $this->_oStepTemplate->set("s", "CHOOSENEXTSTEP", i18n("Please choose an item to start working:"));
            $this->_oStepTemplate->set("s", "FINISHTEXT", i18n("You can now start using Contenido. Please delete the folder named 'setup'!"));

            list($root_path, $root_http_path) = getSystemDirectories();

            $cHTMLButtonLink = new cHTMLButtonLink($root_http_path . "/contenido/", "Backend - CMS");
            $this->_oStepTemplate->set("s", "BACKEND", $cHTMLButtonLink->render());

            if ($_SESSION["setuptype"] == "setup" && $_SESSION["clientmode"] == "CLIENTEXAMPLES") {
                $cHTMLButtonLink = new cHTMLButtonLink($root_http_path . "/cms/", "Frontend - Web");
                $this->_oStepTemplate->set("s", "FRONTEND", $cHTMLButtonLink->render());
            } else {
                $this->_oStepTemplate->set("s", "FRONTEND", "");
            }

            $cHTMLButtonLink = new cHTMLButtonLink("http://www.contenido.org/", "Contenido Website");
            $this->_oStepTemplate->set("s", "WEBSITE", $cHTMLButtonLink->render());

            $cHTMLButtonLink = new cHTMLButtonLink("http://forum.contenido.org/", "Contenido Forum");
            $this->_oStepTemplate->set("s", "FORUM", $cHTMLButtonLink->render());

            $cHTMLButtonLink = new cHTMLButtonLink("http://faq.contenido.org/", "CONTENIDO FAQ");
            $this->_oStepTemplate->set("s", "FAQ", $cHTMLButtonLink->render());
        } else {
            cSetupMask::cSetupMask("templates/setup/forms/setupresultsfail.tpl", $step);
            $this->_oStepTemplate->set("s", "TITLE", i18n("Setup Results"));

            list($sRootPath, $rootWebPath) = getSystemDirectories();

            if (file_exists($sRootPath . "/contenido/logs/setuplog.txt")) {
                $sErrorLink = '<a target="_blank" href="../contenido/logs/setuplog.txt">setuplog.txt</a>';
            } else {
                $sErrorLink = 'setuplog.txt';
            }

            $this->_oStepTemplate->set("s", "DESCRIPTION", sprintf(i18n("An error occured during installation. Please take a look at the file %s (located in &quot;contenido/logs/&quot;) for more information."), $sErrorLink));

            switch ($_SESSION["setuptype"]) {
                case "setup":
                    $this->setNavigation("setup1", "");
                    break;
                case "upgrade":
                    $this->setNavigation("upgrade1", "");
                    break;
                case "migration":
                    $this->setNavigation("migration1", "");
                    break;
            }
        }
    }
}

?>