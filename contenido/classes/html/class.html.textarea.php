<?php

/**
 * This file contains the cHTMLTextarea class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLTextarea class represents a textarea.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTextarea extends cHTMLFormElement {

    protected $_value;

    /**
     * Constructor.
     * Creates an HTML text area.
     *
     * If no additional parameters are specified, the
     * default width is 60 chars, and the height is 5 chars.
     *
     * @param string $name
     *         Name of the element
     * @param string $initvalue [optional]
     *         Initial value of the textarea
     * @param int $width [optional]
     *         width of the textarea
     * @param int $height [optional]
     *         height of the textarea
     * @param string $id [optional]
     *         ID of the element
     * @param string $disabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param string $tabindex [optional]
     *         Tab index for form elements
     * @param string $accesskey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($name, $initvalue = '', $width = '', $height = '', $id = '', $disabled = false, $tabindex = NULL, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'textarea';
        $this->setValue($initvalue);
        $this->_contentlessTag = false;
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setClass($class);
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width
     *         width of the text box
     * @return cHTMLTextarea
     *         $this for chaining
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 50;
        }

        return $this->updateAttribute('cols', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen
     *         maximum input length
     * @return cHTMLTextarea
     *         $this for chaining
     */
    public function setHeight($height) {
        $height = intval($height);

        if ($height <= 0) {
            $height = 5;
        }

        return $this->updateAttribute('rows', $height);
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value
     *         Initial value
     * @return cHTMLTextarea
     *         $this for chaining
     */
    public function setValue($value) {
        $this->_value = $value;

        return $this;
    }

    /**
     * Renders the textarea
     *
     * @return string
     *         Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_value);

        return parent::toHTML();
    }

}
