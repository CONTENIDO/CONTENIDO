<?php
/**
 * This file contains the cHTMLSpan class.
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
 * cHTMLSpan class represents a span element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLSpan extends cHTMLContentElement {

    /**
     * Constructor.
     * Creates an HTML Span element.
     *
     * @param mixed $content String or object with the contents
     * @return void
     */
    public function __construct($content = '', $class = "") {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->_tag = 'span';
        $this->setClass($class);
    }
}
