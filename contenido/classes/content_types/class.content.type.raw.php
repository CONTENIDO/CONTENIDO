<?php

/**
 * This file contains the cContentTypeRaw class.
 *
 * @package Core
 * @subpackage ContentType
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
     * Name of the content type.
     *
     * @var string
     */
    const CONTENT_TYPE = 'CMS_RAW';

    /**
     * Prefix used for posted data.
     * Replaces the property $this->>_prefix.
     *
     * @var string
     */
    const PREFIX = 'raw';

    /**
     * @see cContentTypeAbstract::generateViewCode()
     * @return string
     *         encoded raw settings
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

    /**
     * @see cContentTypeAbstract::generateEditCode()
     * @return string
     *         encoded raw settings
     */
    public function generateEditCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

}
