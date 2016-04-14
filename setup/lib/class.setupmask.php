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

    protected $_tpl = null;

    protected $_stepTemplateClass = null;

    protected $_stepTemplate = '';

    protected $_step = 0;

    protected $_navigationEnabled = false;

    protected $_backstep;

    protected $_nextstep;

    /**
     * cSetupMask constructor.
     * @param string $stepTemplate
     * @param bool $step
     */
    public function __construct($stepTemplate, $step = false) {
        $this->_tpl = new cTemplate();
        $this->_stepTemplateClass = new cTemplate();

        $this->_stepTemplate = $stepTemplate;
        $this->_step = $step;
    }

    /**
     * Old constructor
     * @deprecated [2016-04-14] This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param string $stepTemplate
     * @param bool $step
     */
    public function cSetupMask($stepTemplate, $step = false) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct($stepTemplate, $step);
    }

    public function setNavigation($backstep, $nextstep) {
        $this->_navigationEnabled = true;
        $this->_backstep = $backstep;
        $this->_nextstep = $nextstep;
    }

    public function setHeader($header) {
        if (isset($_SESSION['setuptype'])) {
            $setupType = $_SESSION['setuptype'];
        } else {
            $setupType = '';
        }

        switch ($setupType) {
            case "setup":
                $this->_sHeader = 'Setup - ' . $header;
                break;
            case "upgrade":
                $this->_sHeader = 'Upgrade - ' . $header;
                break;
            default:
                $this->_sHeader = $header;
                break;
        }
    }

    protected function _createNavigation() {
        $link = new cHTMLLink("#");

        $link->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_nextstep."';");
        $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $link->setClass("nav");
        $link->setContent("<span>&raquo;</span>");

        if ($this->_nextstep != "") {
            $this->_stepTemplateClass->set("s", "NEXT", $link->render());
        } else {
            $this->_stepTemplateClass->set("s", "NEXT", '');
        }

        $backlink = new cHTMLLink("#");
        $backlink->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$this->_backstep."';");
        $backlink->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
        $backlink->setClass("nav navBack");
        $backlink->setContent("<span>&laquo;</span>");
        $this->_stepTemplateClass->set("s", "BACK", $backlink->render());
    }

    public function render() {
        if ($this->_navigationEnabled) {
            $this->_createNavigation();
        }

        if ($this->_step !== false) {
            $this->_tpl->set("s", "STEPS", cGenerateSetupStepsDisplay($this->_step));
        } else {
            $this->_tpl->set("s", "STEPS", "");
        }

        $this->_tpl->set("s", "HEADER", $this->_sHeader);
        $this->_tpl->set("s", "TITLE", "CONTENIDO Setup - " . $this->_sHeader);

        $this->_tpl->set("s", "CONTENT", $this->_stepTemplateClass->generate($this->_stepTemplate, true, false));

        $this->_tpl->generate("templates/setup.tpl", false, false);
    }

    public function renderSystemCheck() {
        if ($this->_navigationEnabled) {
            $this->_createNavigation();
        }

        if ($this->_step !== false) {
            $this->_tpl->set("s", "STEPS", '');
        } else {
            $this->_tpl->set("s", "STEPS", '');
        }

        $this->_tpl->set("s", "HEADER", '');
        $this->_tpl->set("s", "TITLE", '');

        $this->_tpl->set("s", "CONTENT", $this->_stepTemplateClass->generate($this->_stepTemplate, true, false));

        $this->_tpl->generate("templates/systemcheck/setup.tpl", false, false);
    }
}

?>