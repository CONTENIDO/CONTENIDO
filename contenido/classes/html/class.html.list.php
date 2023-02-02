<?php

/**
 * This file contains the cHTMLList class.
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
 * cHTMLList class represents a list.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLList extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML list element.
     *
     * @param string $type [optional]
     *         type of the list - ul or ol
     * @param string $id [optional]
     *         the ID of the list element
     * @param string $class [optional]
     *         the class of the list element
     * @param array|string|object $elements [optional]
     *         the elements of this list
     */
    public function __construct($type = 'ul', $id = '', $class = '', $elements = []) {
        parent::__construct($elements, $class, $id);
        if ($type !== 'ul' && $type !== 'ol') {
            $type = 'ul';
        }
        $this->_tag = $type;
    }

}
