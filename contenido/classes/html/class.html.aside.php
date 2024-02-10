<?php

/**
 * This file contains the cHTMLAside class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLAside class represents content which is related to the surrounding
 * content.
 * This element is often used in sidebars.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLAside extends cHTMLContentElement
{

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
    public function __construct($content = '', $class = '', $id = '')
    {
        parent::__construct($content, $class, $id);
        $this->_tag = 'aside';
    }

}
