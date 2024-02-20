<?php

/**
 * This file contains the cHTMLTableHead class.
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
 * cHTMLTableHead class represents a table head.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLTableHead extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     * Creates an HTML th element.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     *         Since CONTENIDO 4.10.2
     */
    public function __construct($content = NULL)
    {
        parent::__construct($content);
        $this->_tag = 'th';
    }

}
