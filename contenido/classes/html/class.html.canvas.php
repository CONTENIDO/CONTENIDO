<?php
/**
 * This file contains the cHTMLCanvas class.
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
 * cHTMLCanvas class can be used for creating graphics.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLCanvas extends cHTMLContentElement {

    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'canvas';
    }

    public function setHeight($height) {
        $this->setAttribute('height', $height);
    }

    public function setWidth($width) {
        $this->setAttribute('width', $width);
    }

}
