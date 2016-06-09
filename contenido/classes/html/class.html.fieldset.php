<?php

/**
 * This file contains the cHTMLFieldset class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call');

/**
 * cHTMLFieldset class represents a fieldset element.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLFieldset extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML fieldset element.
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
        $this->_tag = 'fieldset';
    }

}
