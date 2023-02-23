<?php

/**
 * This file contains the cHTMLListItem class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLListItem class represents a list item.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLListItem extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML li element.
     *
     * @param string $id [optional]
     *         the ID of this list item
     * @param string $class [optional]
     *         the class of this list item
     */
    public function __construct($id = '', $class = '') {
        parent::__construct('', $class, $id);
        $this->_tag = 'li';
    }

}
