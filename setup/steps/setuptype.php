<?php

/**
 * This file contains the setup type chooser class.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

unset($_SESSION['setuptype']);

/**
 * Setup type chooser class
 *
 * @package    Setup
 * @subpackage Setup
 */
class cSetupTypeChooser extends cSetupMask
{

    /**
     * cSetupTypeChooser constructor.
     */
    public function __construct() {
        cSetupMask::__construct("templates/setuptype.tpl");
        $this->setHeader(i18n("Please choose your setup type", "setup"));
        $this->_stepTemplateClass->set("s", "TITLE_SETUP", i18n("Install new CONTENIDO version", "setup"));
        $this->_stepTemplateClass->set("s", "VERSION_SETUP", sprintf(i18n("Version %s", "setup"), CON_SETUP_VERSION));
        $this->_stepTemplateClass->set("s", "DESCRIPTION_SETUP", sprintf(i18n("This setup type will install CONTENIDO %s.", "setup"), CON_SETUP_VERSION)."<br><br>".i18n("Please choose this type if you want to start with an empty or an example installation.", "setup")."<br><br>".i18n("Recommended for new projects.", "setup"));

        $this->_stepTemplateClass->set("s", "TITLE_UPGRADE", i18n("Upgrade existing installation", "setup"));
        $this->_stepTemplateClass->set("s", "VERSION_UPGRADE", sprintf(i18n("Upgrade to %s", "setup"), CON_SETUP_VERSION));
        $this->_stepTemplateClass->set("s", "DESCRIPTION_UPGRADE", i18n("This setup type will upgrade your existing installation (CONTENIDO 4.6.x or later required).", "setup")."<br><br>".i18n("Recommended for existing projects.", "setup"));

        $link = new cHTMLLink("#");
        $link->setClass("nav");
        $link->setContent("<span>&raquo;</span>");
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = 'setup1';");
        $link->attachEventDefinition("setuptypeAttach", "onclick", "document.setupform.setuptype.value = 'setup';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

        $this->_stepTemplateClass->set("s", "NEXT_SETUP", $link->render());

        $link = new cHTMLLink("#");
        $link->setClass("nav");
        $link->setContent("<span>&raquo;</span>");
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = 'upgrade1';");
        $link->attachEventDefinition("setuptypeAttach", "onclick", "document.setupform.setuptype.value = 'upgrade';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $this->_stepTemplateClass->set("s", "NEXT_UPGRADE", $link->render());
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     */
    public function cSetupTypeChooser() {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct();
    }

}

$cSetupStep1 = new cSetupTypeChooser;
$cSetupStep1->render();