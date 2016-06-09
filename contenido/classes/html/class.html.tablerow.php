<?php

/**
 * This file contains the cHTMLTableRow class.
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
 * cHTMLTableRow class represents a table row.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTableRow extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML tr element.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     */
    public function __construct($content = NULL) {
        parent::__construct($content);
        $this->_tag = 'tr';
    }
}
