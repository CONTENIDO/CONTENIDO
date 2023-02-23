<?php

/**
 * This file contains the cHTMLRadiobutton class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLRadiobutton class represents a radio button.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLRadiobutton extends cHTMLFormElement {

    /**
     * Values for the check box
     *
     * @var string
     */
    protected $_value;

    /**
     * The text for the corresponding label
     *
     * @var string
     */
    protected $_labelText;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML radio button element.
     *
     * @param string $name
     *         Name of the element
     * @param string $value
     *         Value of the radio button
     * @param string $id [optional]
     *         ID of the element
     * @param bool $checked [optional]
     *         Is element checked?
     * @param bool $disabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param int|null $tabindex [optional]
     *         Tab index for form elements
     * @param string $accesskey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($name, $value, $id = '', $checked = false, $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey, $class);
        $this->_tag = 'input';
        $this->_value = $value;
        $this->_contentlessTag = true;

        $this->setChecked($checked);
        $this->updateAttribute('type', 'radio');
        $this->updateAttribute('value', $value);
    }

    /**
     * Sets the checked flag.
     *
     * @param bool $checked
     *         If true, the "checked" attribute will be assigned.
     * @return cHTMLRadiobutton
     *         $this for chaining
     */
    public function setChecked($checked) {
        // NOTE: We cast the parameter to boolean, because it could be of another type!
        $checked = cSecurity::toBoolean($checked);
        if ($checked == true) {
            return $this->updateAttribute('checked', 'checked');
        } else {
            return $this->removeAttribute('checked');
        }
    }

    /**
     * Sets a custom label text
     *
     * @param string $text
     *         Text to display
     * @return cHTMLRadiobutton
     *         $this for chaining
     */
    public function setLabelText($text) {
        $this->_labelText = $text;

        return $this;
    }

    /**
     * Renders the option element.
     * Note:
     *
     * If this element has an ID, the value (which equals the text displayed)
     * will be rendered as separate HTML label, if not, it will be displayed
     * as regular text. Displaying the value can be turned off via the
     * parameter.
     *
     * @param bool $renderLabel [optional]
     *         If true, renders a label
     * @return string
     *         Rendered HTML
     */
    public function toHtml($renderLabel = true) {
        if ($renderLabel !== true) {
            return $this->fillSkeleton($this->getAttributes(true));
        }

        // We need the id-attribute render with label
        $id = $this->getAttribute('id');
        if (!$id) {
            $this->advanceID();
        }

        // Render label
        $label = new cHTMLLabel($this->_value, $this->getAttribute('id'));
        if ($this->_labelText != '') {
            $label->text = $this->_labelText;
        }
        $renderedLabel = $label->toHtml();

        return $this->fillSkeleton($this->getAttributes(true)) . $renderedLabel;
    }

}
