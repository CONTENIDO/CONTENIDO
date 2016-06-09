<?php

/**
 * This file contains the cHTMLScript class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLScript class represents a script.
 *
 * @todo Should set attribute type="text/javascript" by default or depending on
 *       doctype!
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLScript extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML script element.
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'script';
    }

}
