<?php
class cTypeGenerator {

    /**
     *
     * @var cfg
     */
    private $cfg = null;

    /**
     *
     * @var db
     */
    private static $db = null;

    /**
     * Constructor function
     */
    public function __construct() {
        $this->cfg = cRegistry::getConfig();

        if (self::$db === null) {
            self::$db = cRegistry::getDb();
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
     *
     * {path_to_contenido_includes}/type/code/include.CMS_HTMLHEAD.code.php
     * for content type CMS_HTMLHEAD
     */
    protected function _getContentTypeCodeFilePathName($type) {
        global $cfg;
        $typeCodeFile = cRegistry::getBackendPath() . $cfg['path']['includes'] . 'type/code/include.' . $type . '.code.php';
        return $typeCodeFile;
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

                $cTypeObject = new $typeClassName(null, $index, $items);
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