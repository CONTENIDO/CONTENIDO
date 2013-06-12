<?php
/**
 * This file contains the cContentTypeImg class.
 *
 * @package Core
 * @subpackage ContentType
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
 * Content type CMS_IMG which displays the path to the selected image.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeImg extends cContentTypeImgeditor {

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
        // there are no raw settings here, because CMS_IMG is not saved
        // separately any more
        // so compute the appropriate raw settings and call the parent
        // constructor with them
        if (strlen($rawSettings) == 0) {
            // if the content type value is not passed, get it from the DB
            if (!isset($contentTypes['CMS_IMGEDITOR'][$id])) {
                $idArtLang = cRegistry::getArticleLanguageId();
                // get the idtype of the CMS_IMGEDITOR content type
                $typeItem = new cApiType();
                $typeItem->loadByType('CMS_IMGEDITOR');
                $idtype = $typeItem->get('idtype');
                // first load the appropriate content entry in order to get the
                // idupl
                $content = new cApiContent();
                $content->loadByMany(array(
                    'idartlang' => $idArtLang,
                    'idtype' => $idtype,
                    'typeid' => $id
                ));
                $rawSettings = $content->get('value');
            } else {
                $rawSettings = $contentTypes['CMS_IMGEDITOR'][$id];
            }
        }

        parent::__construct($rawSettings, $id, $contentTypes);
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_imagePath);
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string escaped HTML code which should be shown if content type is
     *         edited
     */
    public function generateEditCode() {
        return $this->generateViewCode();
    }

}