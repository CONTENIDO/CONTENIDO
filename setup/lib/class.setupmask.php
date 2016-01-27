<?php
/**
 * This file contains the setup mask class.
 *
 * @package    Setup
 * @subpackage GUI
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Setup mask class.
 *
 * @package Setup
 * @subpackage GUI
 */
class cSetupMask
{
    function cSetupMask($sStepTemplate, $iStep = false)
    {
        $this->_oTpl = new cTemplate();
        $this->_oStepTemplate = new cTemplate();

        $this->_sStepTemplate = $sStepTemplate;
        $this->_iStep = $iStep;
        $this->_bNavigationEnabled = false;
    }

    function setNavigation($sBackstep, $sNextstep)
    {
        $this->_bNavigationEnabled = true;
        $this->_bBackstep = $sBackstep;
        $this->_bNextstep = $sNextstep;
    }

    function setHeader($sHeader)
    {
        if (isset($_SESSION['setuptype'])) {
            $sSetupType = $_SESSION['setuptype'];
        } else {
            $sSetupType = '';
        }

        switch ($sSetupType) {
            case "setup":
                $this->_sHeader = 'Setup - ' . $sHeader;
                break;
            case "upgrade":
                $this->_sHeader = 'Upgrade - ' . $sHeader;
                break;
            default:
                $this->_sHeader = $sHeader;
                break;
        }
    }

    function _createNavigation()
    {
        $link = new cHTMLLink("#");

        $link->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_bNextstep."';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $link->setClass("nav");
        $link->setContent("<span>&raquo;</span>");

        if ($this->_bNextstep != "") {
            $this->_oStepTemplate->set("s", "NEXT", $link->render());
        } else {
            $this->_oStepTemplate->set("s", "NEXT", '');
        }

        $backlink = new cHTMLLink("#");
        $backlink->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_bBackstep."';");
        $backlink->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $backlink->setClass("nav navBack");
        $backlink->setContent("<span>&laquo;</span>");
        $this->_oStepTemplate->set("s", "BACK", $backlink->render());
    }

    function render()
    {
        if ($this->_bNavigationEnabled) {
            $this->_createNavigation();
        }

        if ($this->_iStep !== false) {
            $this->_oTpl->set("s", "STEPS", cGenerateSetupStepsDisplay($this->_iStep));
        } else {
            $this->_oTpl->set("s", "STEPS", "");
        }

        $this->_oTpl->set("s", "HEADER", $this->_sHeader);
        $this->_oTpl->set("s", "TITLE", "CONTENIDO Setup - " . $this->_sHeader);

        $this->_oTpl->set("s", "CONTENT", $this->_oStepTemplate->generate($this->_sStepTemplate, true, false));

        $this->_oTpl->generate("templates/setup.tpl", false, false);
    }

    function renderSystemCheck()
    {
        if ($this->_bNavigationEnabled) {
            $this->_createNavigation();
        }

        if ($this->_iStep !== false) {
            $this->_oTpl->set("s", "STEPS", '');
        } else {
            $this->_oTpl->set("s", "STEPS", '');
        }

        $this->_oTpl->set("s", "HEADER", '');
        $this->_oTpl->set("s", "TITLE", '');

        $this->_oTpl->set("s", "CONTENT", $this->_oStepTemplate->generate($this->_sStepTemplate, true, false));

        $this->_oTpl->generate("templates/systemcheck/setup.tpl", false, false);
    }
}

?>