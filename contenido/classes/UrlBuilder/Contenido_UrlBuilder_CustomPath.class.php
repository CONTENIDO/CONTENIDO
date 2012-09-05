<?php

// ##############################################################################
// Old version of VersionImport class
//
// NOTE: Class implemetation below is deprecated and the will be removed in
// future versions of contenido.
// Don't use it, it's still available due to downwards compatibility.

/**
 * Contenido_UrlBuilder_CustomPath
 *
 * @deprecated [2012-09-04] Use cUriBuilderCustomPath instead of this class.
 */
class Contenido_UrlBuilder_CustomPath extends cUriBuilderCustomPath {

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderCustomPath instead of this
     *             function
     */
    public function __construct() {
        cDeprecated("Use class cVersionImport instead");
        parent::__construct();
    }

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderCustomPath instead of this
     *             function
     */
    public static function getInstance() {
        cDeprecated("This function is not supported any longer");
        return cUriBuilderCustomPath::getInstance();
    }

}

?>
