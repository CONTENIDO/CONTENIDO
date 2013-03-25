<?php
/**
 * This file contains the cHTMLTableBody class.
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
 * cHTMLTableBody class represents a table body.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableBody extends cHTMLContentElement {

    /**
     * Creates an HTML tbody element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'tbody';
    }
}
