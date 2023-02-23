<?php

/**
 * This file contains the cHTMLTextbox class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLTextbox class represents a textbox.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTextbox extends cHTMLFormElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML text box.
     *
     * If no additional parameters are specified, the default width is
     * 20 units.
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
     * @param bool $disabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param int|null $tabindex [optional]
     *         Tab index for form elements
     * @param string $accesskey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct(
        $name, $initvalue = '', $width = '', $maxlength = '', $id = '',
        $disabled = false, $tabindex = null, $accesskey = '', $class = ''
    ) {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey, $class);

        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setValue($initvalue);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'text');
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width
     *         width of the text box
     * @return cHTMLTextbox
     *         $this for chaining
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 50;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen
     *         maximum input length
     * @return cHTMLTextbox
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
     * @return cHTMLTextbox
     *         $this for chaining
     */
    public function setValue($value) {
        return $this->updateAttribute('value', $value);
    }

}
