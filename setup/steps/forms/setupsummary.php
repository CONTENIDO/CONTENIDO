<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


class cSetupSetupSummary extends cSetupMask
{
    function cSetupSetupSummary($step, $previous, $next)
    {
        cSetupMask::cSetupMask("templates/setup/forms/setupsummary.tpl", $step);
        $this->setHeader(i18n("Summary"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("Summary"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please check your settings and click on the next button to start the installation"));

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();

        switch ($_SESSION["setuptype"]) {
            case "setup":
                $sType = i18n("Setup");
                break;
            case "upgrade":
                $sType = i18n("Upgrade");
                break;
        }

        $messages = array(
            i18n("Installation type") . ":"   => $sType,
            i18n("Database parameters") . ":" => i18n("Database host") . ": " . $_SESSION["dbhost"] . "<br>" . i18n("Database name") . ": " . $_SESSION["dbname"] . "<br>" . i18n("Database username") . ": " . $_SESSION["dbuser"] . "<br>" . i18n("Table prefix") . ": " . $_SESSION["dbprefix"] . "<br>" . i18n("Database collation") . ": " . $_SESSION["dbcollation"],
        );

        if ($_SESSION["setuptype"] == "setup") {
            $aChoices = array(
                "CLIENTEXAMPLES" => i18n("Client with example modules and example content"),
                "CLIENTMODULES"  => i18n("Client with example modules but without example content"),
                "NOCLIENT"       => i18n("Don't create a client")
            );
            $messages[i18n("Client installation").":"] = $aChoices[$_SESSION["clientmode"]];
        }

        $cHTMLFoldableErrorMessages = array();

        foreach ($messages as $key => $message) {
            $cHTMLFoldableErrorMessages[] = new cHTMLInfoMessage($key, $message);
        }

        $cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);

        $this->_oStepTemplate->set("s", "CONTROL_SETUPSUMMARY", $cHTMLErrorMessageList->render());

        $this->setNavigation($previous, $next);
    }
}

?>