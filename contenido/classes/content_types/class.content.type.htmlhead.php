<?php
/**
 * This file contains the cContentTypeHtmlHead class.
 *
 * @package Core
 * @subpackage Content Type
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
 * Content type CMS_HTMLHEAD which lets the editor enter HTML with the help of a
 * WYSIWYG editor.
 *
 * @package Core
 * @subpackage Content Type
 */
class cContentTypeHtmlHead extends cContentTypeHtml {

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param integer $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     * @return void
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
        // change attributes from the parent class and call the parent
        // constructor
        parent::__construct($rawSettings, $id, $contentTypes);
        $this->_type = 'CMS_HTMLHEAD';
        $this->_prefix = 'htmlhead';
    }

}