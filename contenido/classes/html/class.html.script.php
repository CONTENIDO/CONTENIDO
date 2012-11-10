<?php
/**
 * This file contains the cHTMLScript class.
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
 * cHTMLScript class represents a script.
 *
 * @todo Should set attribute type="text/javascript" by default or depending on
 *       doctype!
 * @package Core
 * @subpackage Frontend
 */
class cHTMLScript extends cHTMLContentElement {

    /**
     * Creates an HTML script element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'script';
    }

}
