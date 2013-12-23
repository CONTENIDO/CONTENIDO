<?php
/**
 * This file contains the cHTMLTableBody class.
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
 * cHTMLTableBody class represents a table body.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTableBody extends cHTMLContentElement {

    /**
     * Creates an HTML tbody element.
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'tbody';
    }
}
