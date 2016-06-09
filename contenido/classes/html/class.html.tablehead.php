<?php

/**
 * This file contains the cHTMLTableHead class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLTableHead class represents a table head.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTableHead extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML th element.
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'th';
    }
}
