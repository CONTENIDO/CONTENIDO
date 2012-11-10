<?php
/**
 * This file contains the cHTMLTableHeader class.
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
 * cHTMLTableHeader class represents a table header.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableHeader extends cHTMLContentElement {

    /**
     * Creates an HTML thead element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'thead';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableHeader() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

}
