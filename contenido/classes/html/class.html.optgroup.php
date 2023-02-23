<?php

/**
 * This file contains the cHTMLOptgroup class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLOptgroup class can be used for creating optgrops
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLOptgroup extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     * @param string $class [optional]
     *         the class of this element
     * @param string $id [optional]
     *         the ID of this element
     */
    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'optgroups';
    }

}
