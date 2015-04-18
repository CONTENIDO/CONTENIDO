<?php
/**
 * This file contains the cContentTypeRaw class.
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content type CMS_RAW which contains hidding texts
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeRaw extends cContentTypeAbstract {

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_RAW[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    public function __construct($rawSettings, $id, array $contentTypes) {

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);

        // set props
        $this->_type = 'CMS_RAW';
        $this->_prefix = 'raw';

    }

    /**
     * @see cContentTypeAbstract::generateViewCode()
     * @return string encoded raw settings
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

    /**
     * @see cContentTypeAbstract::generateEditCode()
     * @return string encoded raw settings
     */
    public function generateEditCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

}
