<?php
/**
 * This file contains the cHTMLDiv class.
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
 * cHTMLDiv class represents a div element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLDiv extends cHTMLContentElement {

    /**
     * Constructor.
     * Creates an HTML Div element.
     *
     * @param mixed $content String or object with the contents
     * @param string $class the class of this element
     * @param string $id the ID of this element
     * @return void
     */
    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'div';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLDiv($content = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($content);
    }

}
