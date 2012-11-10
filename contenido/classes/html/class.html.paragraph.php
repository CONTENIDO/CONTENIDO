<?php
/**
 * This file contains the cHTMLParagraph class.
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
 * cHTMLParagraph class represents a paragraph.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLParagraph extends cHTMLContentElement {

    /**
     * Constructor.
     * Creates an HTML p element.
     *
     * @param mixed $content String or object with the contents
     * @param string $class class of this element
     * @return void
     */
    public function __construct($content = '', $class = '') {
        parent::__construct($content, $class);
        $this->_tag = 'p';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLParagraph($content = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($content);
    }

}
