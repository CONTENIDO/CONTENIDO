<?php

/**
 * This file contains the cHTMLTextarea class.
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
 * cHTMLTextarea class represents a textarea.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLTextarea extends cHTMLFormElement
{

    protected $_value;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML text area.
     *
     * If no additional parameters are specified, the default width is
     * 60 chars, and the height is 5 chars.
     *
     * @param string $name Name of the element
     * @param string $value [optional] Initial value of the textarea
     * @param int $width [optional] Width of the textarea
     * @param int $height [optional] Height of the textarea
     * @param string $id [optional] ID of the element
     * @param bool $disabled [optional] Item disabled flag (non-empty to set disabled)
     * @param ?int $tabindex [optional] Tab index for form elements
     * @param string $accessKey [optional] Key to access the field
     * @param string $class [optional] The class of this element
     */
    public function __construct(
        $name,
        $value = '',
        $width = '',
        $height = '',
        $id = '',
        $disabled = false,
        $tabindex = null,
        $accessKey = '',
        $class = ''
    )
    {
        parent::__construct($name, $id, $disabled, $tabindex, $accessKey, $class);
        $this->_tag = 'textarea';
        $this->setValue($value);
        $this->_contentlessTag = false;
        $this->setWidth($width);
        $this->setHeight($height);
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

        return $this->updateAttribute('cols', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $height Maximum input length
     */
    public function setHeight($height): self
    {
        $height = cSecurity::toInteger($height);
        if ($height <= 0) {
            $height = 5;
        }

        return $this->updateAttribute('rows', $height);
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value Initial value
     */
    public function setValue($value): self
    {
        $this->_value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $this->_setContent($this->_value);

        return parent::toHtml();
    }

}
