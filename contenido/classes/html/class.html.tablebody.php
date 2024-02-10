<?php

/**
 * This file contains the cHTMLTableBody class.
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
 * cHTMLTableBody class represents a table body.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLTableBody extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML tbody element.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_tag = 'tbody';
    }
}
