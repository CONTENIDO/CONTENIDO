<?php

/**
 * This file contains the cContentTypeHtmlhead class.
 *
 * @package Core
 * @subpackage ContentType
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content type CMS_HTMLHEAD which lets the editor enter HTML with the help of a
 * WYSIWYG editor.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeHtmlhead extends cContentTypeHtml {

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int $id
     *         ID of the content type, e.g. 3 if CMS_DATE[3] is used
     * @param array $contentTypes
     *         array containing the values of all content types
     */
    public function __construct($rawSettings, $id, array $contentTypes) {

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);

        // set props
        $this->_type = 'CMS_HTMLHEAD';
        $this->_prefix = 'htmlhead';

    }

}
