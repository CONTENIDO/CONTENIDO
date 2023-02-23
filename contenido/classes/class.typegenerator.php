<?php

/**
 * This file contains content type generator class.
 * TODO: This class needs more documentation.
 *
 * @package Core
 * @subpackage ContentType
 * @author Alexander Scheider
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class generates content types.
 *
 * @package Core
 * @subpackage ContentType
 */
class cTypeGenerator {

    /**
     *
     * @var array
     */
    private $cfg = NULL;

    /**
     *
     * @var cDb
     * @deprecated Since 4.10.2, is not needed anymore
     */
    private static $db = NULL;

    /**
     * Article content helper
     * @since CONTENIDO 4.10.2
     * @var cArticleContentHelper
     */
    private static $articleContentHelper = NULL;

    /**
     *
     * @var array
     */
    private static $a_content = [];

    /**
     *
     * @var int
     */
    private $_idart = NULL;

    /**
     *
     * @var int
     */
    private $_idlang = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct() {
        $this->_idart = cSecurity::toInteger(cRegistry::getArticleId(true));
        $this->_idlang = cSecurity::toInteger(cRegistry::getLanguageId());
        $this->cfg = cRegistry::getConfig();

        if (!isset(self::$a_content[$this->_idart])) {
            $this->fillContent();
        }
    }

    /**
     * Returns the classname for a content type.
     *
     * @param string $type
     *         Content type, e.g. CMS_HTMLHEAD
     * @return string
     *         The classname e.g. cContentTypeHtmlhead for content type CMS_HTMLHEAD
     */
    protected function _getContentTypeClassName($type) {
        return 'cContentType' . ucfirst(cString::toLowerCase(str_replace('CMS_', '', $type)));
    }

    /**
     *
     * @param string $type
     * @return string
     */
    public static function getContentTypeClassName($type)  {
        $contentType = cString::getPartOfString($type, 4);
        return 'cContentType' . cString::toUpperCase($contentType[0]) . cString::toLowerCase(cString::getPartOfString($contentType, 1));
    }

    /**
     * Returns the full path to the include file name of a content type.
     *
     * @param string $type
     *         Content type, e.g. CMS_HTMLHEAD
     * @return string
     *         The full path e.g.
     *         {path_to_contenido_includes}/type/code/include.CMS_HTMLHEAD.code.php
     *         for content type CMS_HTMLHEAD
     */
    protected function _getContentTypeCodeFilePathName($type) {
        return cRegistry::getBackendPath() . $this->cfg['path']['includes'] . 'type/code/include.' . $type . '.code.php';
    }

    /**
     * Fill content from db for current article
     *
     * @throws cDbException|cInvalidArgumentException
     */
    private function fillContent() {
        self::$a_content[$this->_idart] = [];

        if (!isset(self::$articleContentHelper)) {
            self::$articleContentHelper = new cArticleContentHelper();
        }

        self::$a_content[$this->_idart] = self::$articleContentHelper->getContentByIdArtAndIdLang(
            $this->_idart, $this->_idlang
        );
    }

    /**
     *
     * @param string $type
     * @param int    $index
     *
     * @return string
     *
     * @throws cDbException|cException
     */
    private function _processCmsTags($type, $index) {
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();

        $typeList = [];
        while (false !== $oType = $oTypeColl->next()) {
            $typeList[] = $oType->toObject();
        }

        // Replace all CMS_TAGS[]
        foreach ($typeList as $typeItem) {
            if ($type === $typeItem->type) {
                $items[] = $typeItem->type;

                $typeClassName = $this->_getContentTypeClassName($typeItem->type);
                $typeCodeFile = $this->_getContentTypeCodeFilePathName($typeItem->type);

                $settings = self::$a_content[$this->_idart][$typeItem->type][$index] ?? '';
                /** @var cContentTypeAbstract $cTypeObject */
                $cTypeObject = new $typeClassName($settings, $index, $items);
                if (cRegistry::isBackendEditMode()) {
                    $tmp = $cTypeObject->generateEditCode();
                } else {
                    $tmp = $cTypeObject->generateViewCode();
                }

                return $tmp;
            }
        }

        return '';
    }

    /**
     * Helper function to call a private function
     *
     * @param string $type
     * @param int    $index
     *
     * @return string
     *
     * @throws cDbException|cException
     */
    public function getGeneratedCmsTag($type, $index) {
        return $this->_processCmsTags($type, $index);
    }

}
