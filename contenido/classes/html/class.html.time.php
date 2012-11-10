<?php
/**
 * This file contains the cHTMLTime class.
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
 * cHTMLTime class represents a date/time.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTime extends cHTMLContentElement {

    public function __construct($content = '', $class = '', $id = '', $datetime = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'time';
        $this->setDatetime($datetime);
    }

    /**
     * Sets the datetime attribute of this element.
     *
     * @param string $datetime
     */
    public function setDatetime($datetime) {
        $this->setAttribute('datetime', $datetime);
    }

}
