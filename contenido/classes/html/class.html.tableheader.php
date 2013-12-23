<?php
/**
 * This file contains the cHTMLTableHeader class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLTableHeader class represents a table header.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTableHeader extends cHTMLContentElement {

    /**
     * Creates an HTML thead element.
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'thead';
    }
}
