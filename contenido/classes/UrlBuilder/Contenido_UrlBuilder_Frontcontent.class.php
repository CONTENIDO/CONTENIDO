<?php

// ##############################################################################
// Old version of VersionImport class
//
// NOTE: Class implemetation below is deprecated and the will be removed in
// future versions of contenido.
// Don't use it, it's still available due to downwards compatibility.

/**
 * cUriBuilderFrontcontent
 *
 * @deprecated [2012-09-06] Use cUriBuilderFrontcontent instead of this class.
 */
class Contenido_UrlBuilder_Frontcontent extends cUriBuilderFrontcontent {

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilderFrontcontent instead of
     *             this function
     */
    public function __construct() {
       cDeprecated("use constructor from cUriBuilderFrontcontent instead");
        parent::__construct();
    }

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilderFrontcontent instead of
     *             this function
     */
    public static function getInstance() {
        cDeprecated("use getInstance() from cUriBuilderFrontcontent instead");
        return cUriBuilderFrontcontent::getInstance();
    }

}

?>
