<?php

/**
 * This file contains the cHTMLLegend class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call');

/**
 * cHTMLLegend class represents a legend element.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLLegend extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML legend element.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     * @param string $class [optional]
     *         the class of this element
     * @param string $id [optional]
     *         the ID of this element
     */
    public function __construct($content = '', $class = '', $id = '')
    {
        parent::__construct($content, $class, $id);
        $this->_tag = 'legend';
    }

}
