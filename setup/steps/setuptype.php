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


unset($_SESSION["setuptype"]);

class cSetupTypeChooser extends cSetupMask
{
    function cSetupTypeChooser()
    {
        cSetupMask::cSetupMask("templates/setuptype.tpl");
        $this->setHeader(i18n("Please choose your setup type"));
        $this->_oStepTemplate->set("s", "TITLE_SETUP", i18n("Install new CONTENIDO version"));
        $this->_oStepTemplate->set("s", "VERSION_SETUP", sprintf(i18n("Version %s"), C_SETUP_VERSION));
        $this->_oStepTemplate->set("s", "DESCRIPTION_SETUP", sprintf(i18n("This setup type will install CONTENIDO %s."), C_SETUP_VERSION)."<br><br>".i18n("Please choose this type if you want to start with an empty or an example installation.")."<br><br>".i18n("Recommended for new projects."));

        $this->_oStepTemplate->set("s", "TITLE_UPGRADE", i18n("Upgrade existing installation"));
        $this->_oStepTemplate->set("s", "VERSION_UPGRADE", sprintf(i18n("Upgrade to %s"), C_SETUP_VERSION));
        $this->_oStepTemplate->set("s", "DESCRIPTION_UPGRADE", i18n("This setup type will upgrade your existing installation (CONTENIDO 4.6.x or later required).")."<br><br>".i18n("Recommended for existing projects."));

        $this->_oStepTemplate->set("s", "TITLE_MIGRATION", i18n("Migrate existing installation"));
        $this->_oStepTemplate->set("s", "VERSION_MIGRATION", sprintf(i18n("Migrate (Version %s)"), C_SETUP_VERSION));
        $this->_oStepTemplate->set("s", "DESCRIPTION_MIGRATION", i18n("This setup type will help you migrating an existing installation to another server.")."<br><br>".i18n("Recommended for moving projects across servers."));

        $nextSetup = new cHTMLAlphaImage();
        $nextSetup->setSrc(C_SETUP_CONTENIDO_HTML_PATH . "images/submit.gif");
        $nextSetup->setMouseOver(C_SETUP_CONTENIDO_HTML_PATH . "images/submit_hover.gif");
        $nextSetup->setClass("button");

        $link = new cHTMLLink("#");
        $link->setContent($nextSetup);
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = 'setup1';");
        $link->attachEventDefinition("setuptypeAttach", "onclick", "document.setupform.setuptype.value = 'setup';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

        $this->_oStepTemplate->set("s", "NEXT_SETUP", $link->render());

        $nextSetup = new cHTMLAlphaImage();
        $nextSetup->setSrc(C_SETUP_CONTENIDO_HTML_PATH . "images/submit.gif");
        $nextSetup->setMouseOver(C_SETUP_CONTENIDO_HTML_PATH . "images/submit_hover.gif");
        $nextSetup->setClass("button");

        $link = new cHTMLLink("#");
        $link->setContent($nextSetup);
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = 'upgrade1';");
        $link->attachEventDefinition("setuptypeAttach", "onclick", "document.setupform.setuptype.value = 'upgrade';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $this->_oStepTemplate->set("s", "NEXT_UPGRADE", $link->render());

        $nextSetup = new cHTMLAlphaImage;
        $nextSetup->setSrc(C_SETUP_CONTENIDO_HTML_PATH . "images/submit.gif");
        $nextSetup->setMouseOver(C_SETUP_CONTENIDO_HTML_PATH . "images/submit_hover.gif");
        $nextSetup->setClass("button");

        $link = new cHTMLLink("#");
        $link->setContent($nextSetup);
        $link->attachEventDefinition("stepAttach", "onclick", "document.setupform.step.value = 'migration1';");
        $link->attachEventDefinition("setuptypeAttach", "onclick", "document.setupform.setuptype.value = 'migration';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $this->_oStepTemplate->set("s", "NEXT_MIGRATION", $link->render());
    }

}


$cSetupStep1 = new cSetupTypeChooser;
$cSetupStep1->render();


?>