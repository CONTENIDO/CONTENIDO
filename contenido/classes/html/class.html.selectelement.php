<?php

/**
 * This file contains the cHTMLSelectElement class.
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
 * cHTMLSelectElement class represents a select element.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLSelectElement extends cHTMLFormElement {

    /**
     * All cHTMLOptionElements
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Constructor.
     * Creates an HTML select field (aka "DropDown").
     *
     * @param string $name
     *         Name of the element
     * @param int $width
     *         Width of the select element
     * @param string $id
     *         ID of the element
     * @param bool $disabled
     *         Item disabled flag (non-empty to set disabled)
     * @param string $tabindex
     *         Tab index for form elements
     * @param string $accesskey
     *         Key to access the field
     * @param string $class
     *         the class of this element
     */
    public function __construct($name, $width = '', $id = '', $disabled = false, $tabindex = NULL, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'select';
        $this->_contentlessTag = false;
        $this->setClass($class);

        if ($width != "") {
            $this->appendStyleDefinition("width", $width);
        }
    }

    /**
     * Automatically creates and fills cHTMLOptionElements
     *
     * Array format:
     * $stuff = array(
     * array('value', 'title'),
     * array('value', 'title')
     * );
     *
     * or regular key => value arrays:
     * $stuff = array(
     * 'value' => 'title',
     * 'value' => 'title'
     * );
     *
     * @param array $stuff
     *         Array with all items
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function autoFill(array $stuff) {
        foreach ($stuff as $key => $row) {
            if (is_array($row)) {
                $option = new cHTMLOptionElement($row[1], $row[0]);
                $this->addOptionElement($row[0], $option);
            } else {
                $option = new cHTMLOptionElement($row, $key);
                $this->addOptionElement($key, $option);
            }
        }
        return $this;
    }

    /**
     * Adds an cHTMLOptionElement to the number of choices at the specified
     * position.
     *
     * @param string $index
     *         Index of the element
     * @param cHTMLOptionElement $element
     *         Filled cHTMLOptionElement to add
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function addOptionElement($index, cHTMLOptionElement $element) {
        $this->_options[$index] = $element;
        return $this;
    }

    /**
     * Appends a cHTMLOptionElement to the number of choices.
     *
     * @param cHTMLOptionElement $element
     *         Filled cHTMLOptionElement to add
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function appendOptionElement(cHTMLOptionElement $element) {
        $this->_options[] = $element;
        return $this;
    }

    /**
     * Defines that this select element is a multiselect element.
     *
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function setMultiselect() {
        $name = $this->getAttribute('name');
        $strLength = strlen($name);
        if (substr($name, $strLength - 2, $strLength) != '[]') {
            $this->updateAttribute('name', $name . '[]');
        }
        return $this->updateAttribute('multiple', 'multiple');
    }

    /**
     * Defines the size of this select element.
     *
     * @param unknown_type $size
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function setSize($size) {
        return $this->updateAttribute('size', $size);
    }

    /**
     * Sets a specific cHTMLOptionElement to the selected state.
     *
     * @param string $lvalue
     *         Specifies the "value" of the cHTMLOptionElement to
     *        set
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function setDefault($lvalue) {
        if (is_array($lvalue)) {
            foreach ($this->_options as $key => $value) {
                if (in_array($value->getAttribute('value'), $lvalue)) {
                    $value->setSelected(true);
                    $this->_options[$key] = $value;
                } else {
                    $value->setSelected(false);
                    $this->_options[$key] = $value;
                }
            }
        } else {
            foreach ($this->_options as $key => $value) {
                if (strcmp($value->getAttribute('value'), $lvalue) == 0) {
                    $value->setSelected(true);
                    $this->_options[$key] = $value;
                } else {
                    $value->setSelected(false);
                    $this->_options[$key] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Search for the selected elements
     *
     * @return string|bool
     *         "lvalue" or false
     */
    public function getDefault() {
        foreach ($this->_options as $key => $value) {
            if ($value->isSelected()) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Sets specified elements as selected (and all others as unselected)
     *
     * @param array $elements
     *         Array with "values" of the cHTMLOptionElement to set
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function setSelected(array $elements) {
        foreach ($this->_options as $key => $option) {
            if (in_array($option->getAttribute('value'), $elements)) {
                $option->setSelected(true);
                $this->_options[$key] = $option;
            } else {
                $option->setSelected(false);
                $this->_options[$key] = $option;
            }
        }
        return $this;
    }

    /**
     * Renders the select box
     *
     * @return string
     *         Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_options);
        return parent::toHTML();
    }

}
