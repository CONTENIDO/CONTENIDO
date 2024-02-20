<?php

/**
 * This file contains the cHTMLPasswordbox class.
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
 * cHTMLPasswordbox class represents a password form field.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLPasswordbox extends cHTMLFormElement
{

    /**
     * @var bool $_autofill
     */
    protected $_autofill = true;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML password box.
     *
     * If no additional parameters are specified, the default width is
     * 20 units.
     *
     * @param string $name
     *         Name of the element
     * @param string $value [optional]
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
     * @param string $accessKey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($name, $value = '', $width = 0, $maxlength = 0, $id = '', $disabled = false, $tabindex = null, $accessKey = '', $class = '')
    {
        parent::__construct($name, $id, $disabled, $tabindex, $accessKey, $class);
        $this->_tag = 'input';
        $this->setValue($value);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'password');
    }

    /**
     * Sets the autofill property of the element.
     *
     * @param boolean $autofill - The autofill flag
     * @return cHTMLPasswordbox|cHTML
     * @since CONTENIDO 4.10.2
     */
    public function setAutofill(bool $autofill)
    {
        $this->_autofill = $autofill;
        return $this;
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width
     *         width of the text box
     * @return cHTMLPasswordbox
     *         $this for chaining
     */
    public function setWidth($width)
    {
        $width = cSecurity::toInteger($width);

        if ($width <= 0) {
            $width = 20;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxLength
     *         maximum input length
     * @return cHTMLPasswordbox
     *         $this for chaining
     */
    public function setMaxLength($maxLength)
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
     * @param string $value
     *         Initial value
     * @return cHTMLPasswordbox
     *         $this for chaining
     */
    public function setValue($value)
    {
        return $this->updateAttribute('value', $value);
    }

    /**
     * Generates the HTML markup for the input field of type password.
     * Additionally, it deals with the enabled status of the property $_autofill.
     * Setting the autocomplete to "off" will prevent from autocompletion but
     * some browser or password manager may autofill the field with the
     * previous stored value, which is not always wanted.
     * Setting the field initially to readonly and enabling it again after
     * getting focus does the trick!
     *
     * @return string
     * @since CONTENIDO 4.10.2
     * @TODO This function could be moved to somewhere else, because all input, textarea,
     *       select and form elements could use the autocomplete attribute.
     *       But, only input and textarea can have readonly attribute.
     *
     *
     */
    public function toHtml(): string
    {
        $sReadonly = $this->getAttribute('readonly') !== null;

        if ($this->_autofill === true || $sReadonly) {
            // Field can be filled or has already readonly attribute, nothing to do here...
            return parent::toHtml();
        }

        // Handle autocomplete="off", disable the field and enable it again via JavaScript!

        if (!$this->getAttribute('id')) {
            $this->advanceID();
        }
        $this->setAttribute('readonly', 'readonly');

        $html = parent::toHtml();
        // NOTE: If you change the code below, don't forget to adapt the unit test
        //       cHtmlPasswordBoxTest->testAutocomplete()!
        $html .= '
    <script type="text/javascript">
        (function(Con, $) {
            $(function() {
                // Remove readonly attribute on focus
                $("#' . $this->getID() . '").on("focus", function() {
                    $(this).prop("readonly", false);
                });
            });
        })(Con, Con.$);
    </script>
        ';

        return $html;
    }

}
