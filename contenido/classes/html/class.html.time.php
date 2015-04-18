<?php
/**
 * This file contains the cHTMLTime class.
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
 * cHTMLTime class represents a date/time.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTime extends cHTMLContentElement {

    /**
     * Constructor.
     *
     * @param mixed $content
     *         String or object with the contents
     * @param string $class
     *         the class of this element
     * @param string $id
     *         the ID of this element
     * @param string $datetime
     */
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
