<?php
/**
 * This file contains the cHTMLListItem class.
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
 * cHTMLListItem class represents a list item.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLListItem extends cHTMLContentElement {

    /**
     * Creates an HTML li element.
     *
     * @param string $id the ID of this list item
     * @param string $class the class of this list item
     * @return void
     */
    public function __construct($id = '', $class = '') {
        parent::__construct('', $class, $id);
        $this->_tag = 'li';
    }

}
