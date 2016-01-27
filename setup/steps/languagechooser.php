<?php
/**
 * This file contains the setup language chooser class.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

session_unset();

/**
 * Setup language chooser class
 *
 * @package Setup
 * @subpackage Setup
 */
class cSetupLanguageChooser extends cSetupMask
{
    function cSetupLanguageChooser()
    {
        cSetupMask::cSetupMask("templates/languagechooser.tpl");
        $this->setHeader('Version ' . CON_SETUP_VERSION);
        $this->_oStepTemplate->set("s", "DE_HINT", "Diese Anwendung hilft Ihnen bei der Installation von CONTENIDO.");
        $this->_oStepTemplate->set("s", "EN_HINT", "This application will guide you trough the setup process.");
        $this->_oStepTemplate->set("s", "DE_HINT_LANG", "W&auml;hlen Sie bitte die gew&uuml;nschte Sprache f&uuml;r das Setup aus.");
        $this->_oStepTemplate->set("s", "EN_HINT_LANG", "Please choose your language to continue.");

        $langs = array("de_DE" => "Deutsch", "C" => "English");

        $m = "";

        foreach ($langs as $entity => $lang) {
            $test = new cHTMLLanguageLink($entity, $lang, "setuptype");
            $m .= $test->render();
        }

        $this->_oStepTemplate->set("s", "LANGUAGECHOOSER", $m);
    }
}

$cSetupStep1 = new cSetupLanguageChooser();
$cSetupStep1->render();
