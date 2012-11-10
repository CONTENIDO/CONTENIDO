<?php
/**
 * This file contains the cHTMLTableRow class.
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
 * cHTMLTableRow class represents a table row.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableRow extends cHTMLContentElement {

    /**
     * Creates an HTML tr element.
     *
     * @return void
     */
    public function __construct($content = null) {
        parent::__construct($content);
        $this->_tag = 'tr';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableRow() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

}
