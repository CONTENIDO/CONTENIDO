<?php

/**
 * This file contains the cHTMLLabel class.
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
 * cHTMLLabel class represents a form label.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLLabel extends cHTMLContentElement {

    /**
     * The text to display on the label
     *
     * @var string
     */
    public $text;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML label which can be linked to any form element
     * (specified by their ID).
     *
     * A label can be used to link to elements. This is very useful
     * since if a user clicks a label, the linked form element receives
     * the focus (if supported by the user agent).
     *
     * @param string $text
     *         Name of the element
     * @param string $for
     *         ID of the form element to link to.
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($text, $for, $class = '') {
        parent::__construct('', $class);
        $this->_tag = 'label';
        $this->updateAttribute('for', $for);
        $this->text = $text;
    }

    /**
     * Renders the label
     *
     * @return string
     *         Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->text);

        return parent::toHtml();
    }

}
