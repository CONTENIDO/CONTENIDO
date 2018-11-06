<?php

/**
 * This file contains content type generator class.
 * TODO: This class needs more documentation.
 *
 * @package Core
 * @subpackage ContentType
 * @author Alexander Scheider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
     */
    private static $db = NULL;

    /**
     *
     * @var array
     */
    private static $a_content = array();

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
     * @throws cDbException
     */
    public function __construct() {
        $this->_idart = cRegistry::getArticleId(true);
        $this->_idlang = cRegistry::getLanguageId();
        $this->cfg = cRegistry::getConfig();

        if (self::$db === NULL) {
            self::$db = cRegistry::getDb();
        }
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
        $typeClassName = 'cContentType' . ucfirst(cString::toLowerCase(str_replace('CMS_', '', $type)));
        return $typeClassName;
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
        global $cfg;
        $typeCodeFile = cRegistry::getBackendPath() . $cfg['path']['includes'] . 'type/code/include.' . $type . '.code.php';
        return $typeCodeFile;
    }

    /**
     * Fill content from db for current article
     *
     * @throws cDbException
     */
    private function fillContent() {
        self::$a_content[$this->_idart] = array();

        $sql = "SELECT
                    *
                FROM
                    " . $this->cfg["tab"]["content"] . " AS A,
                    " . $this->cfg["tab"]["art_lang"] . " AS B,
                    " . $this->cfg["tab"]["type"] . " AS C
                WHERE
                    A.idtype    = C.idtype AND
                    A.idartlang = B.idartlang AND
                    B.idart     = '" . cSecurity::toInteger($this->_idart) . "' AND
                    B.idlang    = '" . cSecurity::toInteger($this->_idlang) . "'";

        self::$db->query($sql);

        while (self::$db->next_record()) {
            self::$a_content[$this->_idart][self::$db->f("type")][self::$db->f("typeid")] = self::$db->f("value");
        }
    }

    /**
     *
     * @param string $type
     * @param int    $index
     *
     * @return string
     *
     * @throws cDbException
     * @throws cException
     */
    private function _processCmsTags($type, $index) {
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();

        $typeList = array();
        while (false !== $oType = $oTypeColl->next()) {
            $typeList[] = $oType->toObject();
        }

        // Replace all CMS_TAGS[]
        foreach ($typeList as $typeItem) {

            if ($type === $typeItem->type) {

                $items[] = $typeItem->type;

                $typeClassName = $this->_getContentTypeClassName($typeItem->type);
                $typeCodeFile = $this->_getContentTypeCodeFilePathName($typeItem->type);

                $cTypeObject = new $typeClassName(self::$a_content[$this->_idart][$typeItem->type][$index], $index, $items);
                if (cRegistry::isBackendEditMode()) {
                    $tmp = $cTypeObject->generateEditCode();
                } else {
                    $tmp = $cTypeObject->generateViewCode();
                }

                return $tmp;
            }
        }
    }

    /**
     * Helper function to call a private function
     *
     * @param string $type
     * @param int    $index
     *
     * @return string
     *
     * @throws cDbException
     * @throws cException
     */
    public function getGeneratedCmsTag($type, $index) {
        return $this->_processCmsTags($type, $index);
    }
}
