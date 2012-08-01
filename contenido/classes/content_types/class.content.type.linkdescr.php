<?php
/**
 * This file contains the cContentTypeLinkdescr class.
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
 * Content type CMS_LINKDESCR which displays the link description.
 *
 * @package Core
 * @subpackage Content Type
 */
class cContentTypeLinkdescr extends cContentTypeLinkeditor {

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
        // there are no raw settings here, because the image description is now
        // saved in con_upl_meta
        // so compute the appropriate raw settings and call the parent
        // constructor with them
        if (is_null($rawSettings)) {
            // if the content type value is not passed, get it from the DB
            if (!isset($contentTypes['CMS_LINKEDITOR'][$id])) {
                $idArtLang = cRegistry::getArticleLanguageId();
                // get the idtype of the CMS_LINKEDITOR content type
                $typeItem = new cApiType();
                $typeItem->loadByType('CMS_LINKEDITOR');
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
                $rawSettings = $contentTypes['CMS_LINKEDITOR'][$id];
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
        return $this->_encodeForOutput($this->_settings['linkeditor_title']);
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