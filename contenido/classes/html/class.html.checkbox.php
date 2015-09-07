<?php

/**
 * This file contains the cHTMLCheckbox class.
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
 * cHTMLCheckbox class represents a checkbox.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLCheckbox extends cHTMLFormElement {

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
     * Constructor.
     * Creates an HTML checkbox element.
     *
     * @param string $name
     *         Name of the element
     * @param string $value
     *         Value of the checkbox
     * @param string $id [optional]
     *         ID of the element
     * @param bool $checked [optional]
     *         Is element checked?
     * @param string $disabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param string $tabindex [optional]
     *         Tab index for form elements
     * @param string $accesskey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($name, $value, $id = '', $checked = false, $disabled = false, $tabindex = NULL, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_value = $value;
        $this->_contentlessTag = true;

        $this->setChecked($checked);
        $this->updateAttribute('type', 'checkbox');
        $this->updateAttribute('value', $value);
        $this->setClass($class);
    }

    /**
     * Sets the checked flag.
     *
     * @param bool $checked
     *         If true, the "checked" attribute will be assigned.
     * @return cHTMLCheckbox
     *         $this for chaining
     */
    public function setChecked($checked) {
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
     * @return cHTMLCheckbox
     *         $this for chaining
     */
    public function setLabelText($text) {
        $this->_labelText = $text;

        return $this;
    }

    /**
     * Renders the checkbox element.
     * Note:
     *
     * If this element has an ID, the value (which equals the text displayed)
     * will be rendered as seperate HTML label, if not, it will be displayed
     * as regular text. Displaying the value can be turned off via the
     * parameter.
     *
     * @param bool $renderlabel [optional]
     *         If true, renders a label
     * @return string
     *         Rendered HTML
     */
    public function toHtml($renderlabel = true) {
        $id = $this->getAttribute('id');

        $renderedLabel = '';

        if ($renderlabel == true) {
            if ($id != '') {
                $label = new cHTMLLabel($this->_value, $this->getAttribute('id'));

                $label->setClass($this->getAttribute('class'));

                if ($this->_labelText != '') {
                    $label->text = $this->_labelText;
                }

                $renderedLabel = $label->toHtml();
            } else {

                $renderedLabel = $this->_value;

                if ($this->_labelText != '') {
                    $label = new cHTMLLabel($this->_value, $this->getAttribute('id'));
                    $label->text = $this->_labelText;
                    $renderedLabel = $label->toHtml();
                }
            }

            $result = new cHTMLDiv(parent::toHTML() . $renderedLabel);
            $result->setClass('checkbox_wrapper');
            return $result->render();
        } else {
            return parent::toHTML();
        }
    }

}
