<?php

/**
 * This file contains the cHTMLLegend class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @version SVN Revision $Rev:$
 *
 * @author Marcus Gnaß
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call');

/**
 * cHTMLLegend class represents a legend element.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLLegend extends cHTMLContentElement {

    /**
     * Constructor.
     * Creates an HTML legend element.
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
        $this->_tag = 'legend';
    }

}
