<?php
class cTypeGenerator {

    /**
     *
     * @var $cfg
     */
    private $cfg = NULL;

    /**
     *
     * @var $db
     */
    private static $db = NULL;

    /**
     *
     * @var $a_content
     */
    private static $a_content = array();

    /**
     *
     * @var $_idart
     */
    private $_idart = NULL;

    /**
     *
     * @var $_idlang
     */
    private $_idlang = NULL;

    /**
     * Constructor function
     */
    public function __construct() {

        $this->_idart = cRegistry::getArticleId(true);
        $this->_idlang = cRegistry::getLanguageId(true);
        $this->cfg = cRegistry::getConfig();

        if (self::$db === null) {
            self::$db = cRegistry::getDb();
        }
        if (!isset(self::$a_content[$this->_idart])) {
            $this->fillContent();
        }
    }

    /**
     * Returns the classname for a content type.
     *
     * @param string $type Content type, e. g. CMS_HTMLHEAD
     * @return string The classname e. g. cContentTypeHtmlhead for content type
     *         CMS_HTMLHEAD
     */
    protected function _getContentTypeClassName($type) {
        $typeClassName = 'cContentType' . ucfirst(strtolower(str_replace('CMS_', '', $type)));
        return $typeClassName;
    }

    /**
     * Returns the full path to the include file name of a content type.
     *
     * @param string $type Content type, e. g. CMS_HTMLHEAD
     * @return string The full path e. g.
     * {path_to_contenido_includes}/type/code/include.CMS_HTMLHEAD.code.php
     * for content type CMS_HTMLHEAD
     */
    protected function _getContentTypeCodeFilePathName($type) {
        global $cfg;
        $typeCodeFile = cRegistry::getBackendPath() . $cfg['path']['includes'] . 'type/code/include.' . $type . '.code.php';
        return $typeCodeFile;
    }

    /**
     * Fill content from db for current article
     *
     * @return void
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
                    B.idart     = '" . Contenido_Security::toInteger($this->_idart) . "' AND
                    B.idlang    = '" . Contenido_Security::toInteger($this->_idlang) . "'";

        self::$db->query($sql);

        while (self::$db->next_record()) {
            self::$a_content[$this->_idart][self::$db->f("type")][self::$db->f("typeid")] = self::$db->f("value");
        }
    }

    /**
     *
     * @param string $type
     * @param int $index
     */
    private function _processCmsTags($type, $index) {
        $_typeList = array();
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();
        while ($oType = $oTypeColl->next()) {
            $_typeList[] = $oType->toObject();
        }

        // Replace all CMS_TAGS[]
        foreach ($_typeList as $_typeItem) {

            if ($type === $_typeItem->type) {

                $items[] = $_typeItem->type;

                $typeClassName = $this->_getContentTypeClassName($_typeItem->type);
                $typeCodeFile = $this->_getContentTypeCodeFilePathName($_typeItem->type);
                $cTypeObject = new $typeClassName(self::$a_content[$this->_idart][$_typeItem->type][$index], $index, $items);
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
     * @param int $index
     *
     * @return array
     */
    public function getGeneratedCmsTag($type, $index) {
        return $this->_processCmsTags($type, $index);
    }

}

?>