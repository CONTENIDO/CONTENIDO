<?php
/**
 * This file contains the cHTMLList class.
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
 * cHTMLList class represents a list.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLList extends cHTMLContentElement {

    /**
     * Creates an HTML list element.
     *
     * @param string $type type of the list - ul or ol
     * @param string $id the ID of the list element
     * @param string $class the class of the list element
     * @param array|string|object $elements the elements of this list
     * @return void
     */
    public function __construct($type = 'ul', $id = '', $class = '', $elements = array()) {
        parent::__construct($elements, $class, $id);
        if ($type !== 'ul' && $type !== 'ol') {
            $type = 'ul';
        }
        $this->_tag = $type;
    }

}
