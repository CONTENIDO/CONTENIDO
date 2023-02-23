<?php
/**
 * This file contains the setup summary setup mask.
 *
 * @package    Setup
 * @subpackage Form
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Setup summary setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupSetupSummary extends cSetupMask
{

    /**
     * cSetupSetupSummary constructor.
     * @param $step
     * @param $previous
     * @param $next
     */
    public function __construct($step, $previous, $next) {
        cSetupMask::__construct("templates/setup/forms/setupsummary.tpl", $step);
        $this->setHeader(i18n("Summary", "setup"));
        $this->_stepTemplateClass->set("s", "TITLE", i18n("Summary", "setup"));
        $this->_stepTemplateClass->set("s", "DESCRIPTION", i18n("Please check your settings and click on the next button to start the installation", "setup"));

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();

        switch ($_SESSION['setuptype']) {
            case 'setup':
                $sType = i18n("Setup", "setup");
                break;
            case 'upgrade':
                $sType = i18n("Upgrade", "setup");
                break;
        }

        $messages = [
            i18n("Installation type", "setup") . ":" => $sType,
        ];

        // Database summary
        $dbMessages = [
            i18n("Database parameters", "setup") . ":" => i18n("Database host", "setup") . ": " . $_SESSION['dbhost'],
            i18n("Database name", "setup") . ": " . $_SESSION['dbname'],
            i18n("Database username", "setup") . ": " . $_SESSION['dbuser'],
            i18n("Table prefix", "setup") . ": " . $_SESSION['dbprefix'],
            i18n("Database character set", "setup") . ": " . $_SESSION['dbcharset'],
        ];
        if (is_array($_SESSION['dboptions'])) {
            if (!empty($_SESSION['dboptions'][MYSQLI_INIT_COMMAND])) {
                $dbMessages[] = i18n("Database option MYSQLI_INIT_COMMAND", "setup") . ": " . $_SESSION['dboptions'][MYSQLI_INIT_COMMAND];
            }
        }
        $messages[i18n("Database parameters", "setup") . ":" ] = implode("<br>", $dbMessages);

        // Client summary
        if ($_SESSION['setuptype'] == 'setup') {
            $aChoices = [
                "CLIENTEXAMPLES" => i18n("Client with example modules and example content", "setup"),
                "CLIENTMODULES"  => i18n("Client with example modules but without example content", "setup"),
                "NOCLIENT"       => i18n("Don't create a client", "setup")
            ];
            $messages[i18n("Client installation", "setup").":"] = $aChoices[$_SESSION['clientmode']];
        }

        $cHTMLFoldableErrorMessages = [];

        foreach ($messages as $key => $message) {
            $cHTMLFoldableErrorMessages[] = new cHTMLInfoMessage($key, $message);
        }

        $cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);

        $this->_stepTemplateClass->set("s", "CONTROL_SETUPSUMMARY", $cHTMLErrorMessageList->render());

        $this->setNavigation($previous, $next);
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param $step
     * @param $previous
     * @param $next
     */
    public function cSetupSetupSummary($step, $previous, $next) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($step, $previous, $next);
    }
}

?>