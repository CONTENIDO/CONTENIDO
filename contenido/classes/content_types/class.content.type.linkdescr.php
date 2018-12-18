<?php

/**
 * This file contains the cContentTypeLinkdescr class.
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
 * Content type CMS_LINKDESCR which displays the link description.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeLinkdescr extends cContentTypeLinkeditor {
    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int    $id
     *         ID of the content type, e.g. 3 if CMS_DATE[3] is used
     * @param array  $contentTypes
     *         array containing the values of all content types
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($rawSettings, $id, array $contentTypes) {

        // There are no raw settings here, because CMS_LINKEDITOR is now
        // saved in con_upl_meta. So compute the appropriate raw settings
        // and call the parent constructor with them.
        if (!cXmlBase::isValidXML($rawSettings)) {
            $rawSettings = $this->_getRawSettings("CMS_LINKEDITOR", $id, $contentTypes);
        }

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string
     *         escaped HTML code which sould be shown if content type is shown in frontend
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_settings['linkeditor_title']);
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is edited
     */
    public function generateEditCode() {
        return $this->generateViewCode();
    }

}
