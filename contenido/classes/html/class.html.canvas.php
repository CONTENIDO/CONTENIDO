<?php

/**
 * This file contains the cHTMLCanvas class.
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
 * cHTMLCanvas class can be used for creating graphics.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLCanvas extends cHTMLContentElement {

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
        parent::__construct($content, $class, $id);
        $this->_tag = 'canvas';
    }

    /**
     *
     * @param unknown_type $height
     */
    public function setHeight($height) {
        $this->setAttribute('height', $height);
    }

    /**
     *
     * @param unknown_type $width
     */
    public function setWidth($width) {
        $this->setAttribute('width', $width);
    }

}
