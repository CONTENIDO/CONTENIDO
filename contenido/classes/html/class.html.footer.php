<?php
/**
 * This file contains the cHTMLFooter class.
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
 * cHTMLFooter class represents a footer.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLFooter extends cHTMLContentElement {

    /**
     * Constructor.
     *
     * @param mixed $content
     *         String or object with the contents
     * @param string $class
     *         the class of this element
     * @param string $id
     *         the ID of this element
     */
    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'footer';
    }

}
