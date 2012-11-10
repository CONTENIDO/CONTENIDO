<?php
/**
 * This file contains the cHTMLContentElement class.
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
 * cHTMLContentElement class represents an element which can contain content.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLContentElement extends cHTML {

    /**
     * Constructor.
     *
     * @param mixed $content String or object with the contents
     * @param string $class the class of this element
     * @param string $id the ID of this element
     * @return void
     */
    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->setClass($class);
        $this->setID($id);
    }

    /**
     * Sets the element's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLContentElement $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLContentElement $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}
