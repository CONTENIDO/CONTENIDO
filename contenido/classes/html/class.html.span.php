<?php
/**
 * This file contains the cHTMLSpan class.
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
 * cHTMLSpan class represents a span element.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLSpan extends cHTMLContentElement {

    /**
     * Constructor.
     * Creates an HTML Span element.
     *
     * @param mixed $content String or object with the contents
     */
    public function __construct($content = '', $class = "") {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->_tag = 'span';
        $this->setClass($class);
    }
}
