<?php
/**
 * This file contains the cHTMLLabel class.
 *
 * @package Core
 * @subpackage HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * cHTMLLabel class represents a form label.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLLabel extends cHTMLContentElement {

    /**
     * The text to display on the label
     *
     * @var string
     */
    public $text;

    /**
     * Constructor.
     * Creates an HTML label which can be linked
     * to any form element (specified by their ID).
     *
     * A label can be used to link to elements. This is very useful
     * since if a user clicks a label, the linked form element receives
     * the focus (if supported by the user agent).
     *
     * @param string $text Name of the element
     * @param string $for ID of the form element to link to.
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($text, $for, $class = '') {
        parent::__construct('', $class);
        $this->_tag = 'label';
        $this->updateAttribute('for', $for);
        $this->text = $text;
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLLabel($text, $for) {
        cDeprecated('Use __construct() instead');
        $this->__construct($text, $for);
    }

    /**
     * Renders the label
     *
     * @return string Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->text);

        return parent::toHTML();
    }

}
