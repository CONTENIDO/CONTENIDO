<?php

/**
 * This file contains the cHTMLPasswordbox class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLPasswordbox class represents a password form field.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLPasswordbox extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML password box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param string $name
     *         Name of the element
     * @param string $initvalue [optional]
     *         Initial value of the box
     * @param int $width [optional]
     *         width of the text box
     * @param int $maxlength [optional]
     *         maximum input length of the box
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
    public function __construct($name, $initvalue = '', $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = NULL, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->setValue($initvalue);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'password');
        $this->setClass($class);
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width
     *         width of the text box
     * @return cHTMLPasswordbox
     *         $this for chaining
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 20;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen
     *         maximum input length
     * @return cHTMLPasswordbox
     *         $this for chaining
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            return $this->removeAttribute('maxlength');
        } else {
            return $this->updateAttribute('maxlength', $maxlen);
        }
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value
     *         Initial value
     * @return cHTMLPasswordbox
     *         $this for chaining
     */
    public function setValue($value) {
        return $this->updateAttribute('value', $value);
    }

}
