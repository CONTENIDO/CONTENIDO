<?php

/**
 * This file contains the cHTMLOptionElement class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLOptionElement class represents a select option element.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLOptionElement extends cHTMLFormElement {

    /**
     * Title to display
     *
     * @var string
     */
    protected $_title;

    /**
     * Constructor.
     * Creates an HTML option element.
     *
     * @param string $title
     *         Displayed title of the element
     * @param string $value
     *         Value of the option
     * @param bool $selected [optional]
     *         If true, element is selected
     * @param bool $disabled [optional]
     *         If true, element is disabled
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($title, $value, $selected = false, $disabled = false, $class = '') {
        cHTML::__construct();
        $this->_tag = 'option';
        $this->_title = $title;

        $this->updateAttribute('value', $value);
        $this->_contentlessTag = false;

        $this->setSelected($selected);
        $this->setDisabled($disabled);
        $this->setClass($class);
    }

    /**
     * Sets the selected flag
     *
     * @param bool $selected
     *         If true, adds the "selected" attribute
     * @return cHTMLOptionElement
     *         $this for chaining
     */
    public function setSelected($selected) {
        if ($selected == true) {
            return $this->updateAttribute('selected', 'selected');
        } else {
            return $this->removeAttribute('selected');
        }
    }

    /**
     * Checks whether this option element is selected.
     *
     * @return bool
     *         whether this option element is selected
     */
    public function isSelected() {
        return $this->getAttribute('selected') === 'selected';
    }

    /**
     * Sets the disabled flag
     *
     * @param bool $disabled
     *         If true, adds the "disabled" attribute
     * @return cHTMLOptionElement
     *         $this for chaining
     */
    public function setDisabled($disabled) {
        if ($disabled == true) {
            return $this->updateAttribute('disabled', 'disabled');
        } else {
            return $this->removeAttribute('disabled');
        }
    }

    /**
     * Renders the option element.
     * Note:
     * the cHTMLSelectElement renders the options by itself.
     *
     * @return string
     *         Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_title);

        return parent::toHTML();
    }

}
