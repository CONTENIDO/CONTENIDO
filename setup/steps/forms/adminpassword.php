<?php
/**
 * This file contains the admin password setup mask.
 *
 * @package    Setup
 * @subpackage Form
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Admin password setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupAdminPassword extends cSetupMask {
    function cSetupAdminPassword($step, $previous, $next) {
        global $cfg;

        cSetupMask::cSetupMask("templates/setup/forms/adminpassword.tpl", $step);

        cArray::initializeKey($_SESSION, "adminmail", "");
        cArray::initializeKey($_SESSION, "adminpass", "");
        cArray::initializeKey($_SESSION, "adminpassrepeat", "");

        $this->setHeader(i18n("Administrator password"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("Administrator password"));

        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please enter the password for the default administrator account sysadmin and specify it's mail address in case you forgot your entered password."));

        if ($_SESSION["adminpass"] != "") {
            $displayadminpass = str_repeat("*", strlen($_SESSION["adminpass"]));
        } else {
            $displayadminpass = "";
        }

        if ($_SESSION["adminpassrepeat"] != "") {
            $displayadminpassrepeat = str_repeat("*", strlen($_SESSION["adminpassrepeat"]));
        } else {
            $displayadminpassrepeat = "";
        }

        $adminmail = new cHTMLTextbox("adminmail", $_SESSION["adminmail"], 30, 255);
        $adminpass = new cHTMLPasswordbox("adminpass", $displayadminpass, 30, 255);
        $adminpassrepeat = new cHTMLPasswordbox("adminpassrepeat", $displayadminpassrepeat, 30, 255);

        $adminpass->attachEventDefinition("onchange handler", "onchange", "document.setupform.adminpass_changed.value = 'true';");
        $adminpass->attachEventDefinition("onchange handler", "onkeypress", "document.setupform.adminpass_changed.value = 'true';");

        $adminpassrepeat->attachEventDefinition("onchange handler", "onchange", "document.setupform.adminpassrepeat_changed.value = 'true';");
        $adminpassrepeat->attachEventDefinition("onchange handler", "onkeypress", "document.setupform.adminpassrepeat_changed.value = 'true';");

        $adminpass_hidden = new cHTMLHiddenField("adminpass_changed", "false");
        $adminpassrepeat_hidden = new cHTMLHiddenField("adminpassrepeat_changed", "false");

        $this->_oStepTemplate->set("s", "LABEL_ADMINPASS", i18n("Administrator password"));
        $this->_oStepTemplate->set("s", "LABEL_ADMINPASSREPEAT", i18n("Administrator password") . " " . i18n("(repeat)"));
        $this->_oStepTemplate->set("s", "LABEL_ADMINMAIL", i18n("Administrator mail address"));

        $this->_oStepTemplate->set("s", "INPUT_ADMINPASS", $adminpass->render().$adminpass_hidden->render());
        $this->_oStepTemplate->set("s", "INPUT_ADMINPASSREPEAT", $adminpassrepeat->render().$adminpassrepeat_hidden->render());
        $this->_oStepTemplate->set("s", "INPUT_ADMINMAIL", $adminmail->render());

        $this->setNavigation($previous, $next);
    }

    function _createNavigation()
    {
        $link = new cHTMLLink("#");

       // if ($_SESSION["setuptype"] == "setup") {
            $checkScript = sprintf(
                "var msg = ''; if (document.setupform.adminpass.value == '' || document.setupform.adminpassrepeat.value == '') { msg += '%s '; } if (msg == '' && document.setupform.adminpass.value != document.setupform.adminpassrepeat.value) { msg += '%s '; } if (msg == '' && document.setupform.adminmail.value == '') { msg += '%s '; } if (msg == '') { document.setupform.submit(); } else { alert(msg); }",
                i18n("You need to enter a password."),
                i18n("The entered passwords are not matching."),
                i18n("You need to enter a mail address.")
            );

            $link->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_bNextstep."';");
            $link->attachEventDefinition("submitAttach", "onclick", $checkScript);
       // } else {
       //     $link->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_bNextstep."'; document.setupform.submit();");
       // }

        $nextSetup = new cHTMLAlphaImage();
        $nextSetup->setSrc(CON_SETUP_CONTENIDO_HTML_PATH . "images/submit.gif");
        $nextSetup->setMouseOver(CON_SETUP_CONTENIDO_HTML_PATH . "images/submit_hover.gif");
        $nextSetup->setClass("button");

        $link->setContent($nextSetup);

        $this->_oStepTemplate->set("s", "NEXT", $link->render());

        $backlink = new cHTMLLink("#");
        $backlink->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_bBackstep."';");
        $backlink->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

        $backSetup = new cHTMLAlphaImage();
        $backSetup->setSrc("images/controls/back.gif");
        $backSetup->setMouseOver("images/controls/back.gif");
        $backSetup->setClass("button");
        $backSetup->setStyle("margin-right: 10px");
        $backlink->setContent($backSetup);
        $this->_oStepTemplate->set("s", "BACK", $backlink->render());
    }
}

?>