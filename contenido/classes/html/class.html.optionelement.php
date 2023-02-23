<?php

/**
 * This file contains the cHTMLOptionElement class.
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
 * cHTMLOptionElement class represents a select option element.
 *
 * @package    Core
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
     * Constructor to create an instance of this class.
     *
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
        parent::__construct('', '', $disabled, '', '', $class);
        $this->_tag = 'option';
        $this->_title = $title;

        $this->updateAttribute('value', $value);
        $this->_contentlessTag = false;

        $this->setSelected($selected);
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
     * Renders the option element.
     * Note:
     * the cHTMLSelectElement renders the options by itself.
     *
     * @return string
     *         Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_title);

        return parent::toHtml();
    }

    /**
     * Generates the indentation used to display a structure tree, e.g. within a select box.
     *
     * @since CONTENIDO 4.10.2
     * @param int $level The category level
     * @param int $prefixAmount Initial amount of indentation characters to start with
     * @param int $levelAmount The amount of indentation characters for each level
     * @param string $character The indentation character itself
     * @return string Generated indentation string
     */
    public static function indent(
        int $level, int $prefixAmount = 2, int $levelAmount = 4,
        string $character = '&nbsp;'
    ): string
    {
        $prefixStr = str_repeat($character, max(0, $prefixAmount));
        $levelStr = str_repeat($character, max(0, $levelAmount));
        return $prefixStr . str_repeat($levelStr, max(0, $level));
    }

}
