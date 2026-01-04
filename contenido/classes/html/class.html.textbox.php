<?php

/**
 * This file contains the cHTMLTextbox class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLTextbox class represents a textbox.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLTextbox extends cHTMLFormElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML text box.
     *
     * If no additional parameters are specified, the default width is 20 units.
     *
     * @param string $name Name of the element
     * @param string $value [optional] Initial value of the box
     * @param int $width [optional] Width of the text box
     * @param int $maxLength [optional] Maximum input length of the box
     * @param string $id [optional] ID of the element
     * @param bool $disabled [optional] Item disabled flag (non-empty to set disabled)
     * @param ?int $tabindex [optional] Tab index for form elements
     * @param string $accessKey [optional] Key to access the field
     * @param string $class [optional] The class of this element
     */
    public function __construct(
        $name,
        $value = '',
        $width = 0,
        $maxLength = 0,
        $id = '',
        $disabled = false,
        $tabindex = null,
        $accessKey = '',
        $class = ''
    )
    {
        parent::__construct($name, $id, $disabled, $tabindex, $accessKey, $class);

        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setValue($value);

        $this->setWidth($width);
        $this->setMaxLength($maxLength);

        $this->updateAttribute('type', 'text');
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width Width of the text box
     */
    public function setWidth($width): self
    {
        $width = cSecurity::toInteger($width);
        if ($width <= 0) {
            $width = 50;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxLength Maximum input length
     */
    public function setMaxLength($maxLength): self
    {
        $maxLength = cSecurity::toInteger($maxLength);
        if ($maxLength <= 0) {
            return $this->removeAttribute('maxlength');
        } else {
            return $this->updateAttribute('maxlength', $maxLength);
        }
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value Initial value
     */
    public function setValue($value): self
    {
        return $this->updateAttribute('value', $value);
    }

}
