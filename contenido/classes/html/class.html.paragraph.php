<?php

/**
 * This file contains the cHTMLParagraph class.
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
 * cHTMLParagraph class represents a paragraph.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLParagraph extends cHTMLContentElement {

    /**
     * Constructor.
     * Creates an HTML p element.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     * @param string $class [optional]
     *         class of this element
     */
    public function __construct($content = '', $class = '') {
        parent::__construct($content, $class);
        $this->_tag = 'p';
    }
}
