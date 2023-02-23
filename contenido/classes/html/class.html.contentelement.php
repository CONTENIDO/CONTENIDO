<?php

/**
 * This file contains the cHTMLContentElement class.
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
 * cHTMLContentElement class represents an element which can contain content.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLContentElement extends cHTML {

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     * @param string $class [optional]
     *         the class of this element
     * @param string $id [optional]
     *         the ID of this element
     */
    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->setID($id);
        $this->setClass($class);
    }

    /**
     * Sets the element's content
     *
     * @param string|object|array $content
     *         String with the content or a cHTML object to render or an array
     *         of strings / objects.
     * @return cHTMLContentElement
     *         $this for chaining
     */
    public function setContent($content) {
        $this->_setContent($content);
        return $this;
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content
     *         String with the content or a cHTML object to render
     *         or an array of strings / objects.
     * @return cHTMLContentElement
     *         $this for chaining
     */
    public function appendContent($content) {
        $this->_appendContent($content);
        return $this;
    }

}
