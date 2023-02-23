<?php

/**
 * This file contains the cHTMLSpan class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
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
     * Constructor to create an instance of this class.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($content = '', $class = "") {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->_tag = 'span';
        $this->setClass($class);
    }

}
