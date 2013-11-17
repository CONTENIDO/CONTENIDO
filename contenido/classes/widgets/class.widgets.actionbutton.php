<?php
/**
 * This file contains the clickable action and clickable question action widget
 * class.
 *
 * @package Core
 * @subpackage GUI
 * @version SVN Revision $Rev:$
 *
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cApiClickableAction is a subclass of cApiAction.
 * It provides an image for visual representation. Inherited classes should call
 * the "setNamedAction" operation in their constructors;
 * on-the-fly-implementations should call it directly after creating an object
 * instance.
 * @deprecated 2013-11-17 This class is not longer supported.
 * @package Core
 * @subpackage GUI
 */
class cApiClickableAction extends cApiAction {

    /**
     * Help text
     *
     * @access private
     */
    private $_helpText;

    /**
     * cHTMLLink for rendering the icon
     *
     * @access private
     */
    protected $_link;

    /**
     * cHTMLImage for rendering the icon
     *
     * @access private
     */
    private $_img;

    /**
     *
     * @var string
     */
    const QUESTIONACTION_PROMPT = 'prompt';

    /**
     *
     * @var string
     */
    const QUESTIONACTION_YESNO = 'yesno';

    /**
     */
    public function __construct() {
        global $area;

        parent::__construct();

        $this->_area = $area;
        $this->_frame = 4;
        $this->_target = "right_bottom";

        $this->_link = new cHTMLLink();
        $this->_img = new cHTMLImage();
        $this->_img->setBorder(0);
        $this->_img->setStyle("padding-left: 1px; padding-right: 1px;");

        $this->_parameters = array();

        $this->setEnabled();

        cDeprecated("This class is not longer supported.");
    }

    /**
     * Sets the action icon for this action.
     *
     * @param string icon Path to the icon. Relative to the backend, if not
     *        passed as absolute path.
     * @return void
     */
    public function setIcon($icon) {
        $this->_img->setSrc($icon);
    }

    /**
     *
     * @return cHTMLImage
     */
    public function getIcon() {
        return $this->_img;
    }

    /**
     * Sets this class to use a specific action, example "con_makestart".
     *
     * @param string actionName Name of the action to use. This action must
     *        exist in the actions table before
     *        using it, otherwise, this method will fail.
     * @return void
     */
    public function setNamedAction($actionName) {
        if ($this->loadBy("name", $actionName) !== false) {
            $a = new cApiArea();
            $a->loadByPrimaryKey($this->get("idarea"));

            $this->_namedAction = $actionName;
            $this->_area = $a->get("name");

            $this->_parameters = array();
            $this->_wantParameters = array();
        }
    }

    /**
     */
    public function setDisabled() {
        $this->_enabled = false;
        $this->_onDisable();
    }

    /**
     */
    public function setEnabled() {
        $this->_enabled = true;
        $this->_onEnable();
    }

    /**
     */
    protected function _onDisable() {
    }

    /**
     */
    protected function _onEnable() {
    }

    /**
     * Change linked area
     */
    public function changeArea($sArea) {
        $this->_area = $sArea;
    }

    /**
     *
     * @param string $parameter
     */
    public function wantParameter($parameter) {
        $this->_wantParameters[] = $parameter;

        $this->_wantParameters = array_unique($this->_wantParameters);
    }

    /**
     * sets the help text for this action.
     *
     * @param string helptext The helptext to apply
     * @return void
     */
    public function setHelpText($helptext) {
        $this->_helpText = $helptext;
    }

    /**
     */
    public function getHelpText() {
        return $this->_helpText;
    }

    /**
     *
     * @param string $name
     * @param string $value
     */
    public function setParameter($name, $value) {
        $this->_parameters[$name] = $value;
    }

    /**
     *
     * @param mixed $parameters
     * @return boolean
     */
    public function process($parameters) {
        echo "Process should be overridden";
        return false;
    }

    /**
     *
     * @return string
     */
    public function render() {
        $this->_img->setAlt($this->_helpText);

        foreach ($this->_parameters as $name => $value) {
            $this->_link->setCustom($name, $value);
        }

        $this->_link->setAlt($this->_helpText);
        $this->_link->setCLink($this->_area, $this->_frame, $this->_namedAction);
        $this->_link->setTargetFrame($this->_target);
        $this->_link->setContent($this->_img);

        if ($this->_enabled == true) {
            return ($this->_link->render());
        } else {
            return ($this->_img->render());
        }
    }

    /**
     *
     * @return string
     */
    public function renderText() {
        foreach ($this->_parameters as $name => $value) {
            $this->_link->setCustom($name, $value);
        }

        $this->_link->setAlt($this->_helpText);
        $this->_link->setCLink($this->_area, $this->_frame, $this->_namedAction);
        $this->_link->setTargetFrame($this->_target);
        $this->_link->setContent($this->_helpText);

        if ($this->_enabled == true) {
            return ($this->_link->render());
        } else {
            return ($this->_helpText);
        }
    }
}

/**
 * Clickable question action class.
 * @deprecated 2013-11-17 This class is not longer supported.
 * @package Core
 * @subpackage GUI
 */
class cApiClickableQuestionAction extends cApiClickableAction {

    /**
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     *
     * @param string $mode
     */
    public function setQuestionMode($mode) {
        $this->_mode = $mode;
    }

    /**
     *
     * @param string $question
     */
    public function setQuestion($question) {
        $this->_question = $question;
    }

    /**
     *
     * @param string $var
     */
    public function setResultVar($var) {
        $this->_resultVar = $var;
    }

    /**
     * (non-PHPdoc)
     *
     * @see cApiClickableAction::render()
     */
    public function render() {
        switch ($this->_mode) {
            case self::QUESTIONACTION_PROMPT:
                $this->_link->attachEventDefinition("_" . get_class($this) . rand(), "onclick", 'var answer = prompt("' . conHtmlSpecialChars($this->_question) . '");if (answer == null) {return false;} else { this.href = this.href + "&' . $this->_resultVar . '="+answer; return true;}');
                break;
            case self::QUESTIONACTION_YESNO:
            default:
                $this->_link->attachEventDefinition("_" . get_class($this) . rand(), "onclick", 'var answer = confirm("' . conHtmlSpecialChars($this->_question) . '");if (answer == false) {return false;} else { return true;}');
                break;
        }

        return parent::render();
    }
}
