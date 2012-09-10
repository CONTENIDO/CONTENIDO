<?php

// ##############################################################################
// Old version of VersionImport class
//
// NOTE: Class implemetation below is deprecated and the will be removed in
// future versions of contenido.
// Don't use it, it's still available due to downwards compatibility.

/**
 * Contenido_UrlBuilder_Custom
 *
 * @deprecated [2012-09-06] Use cUriBuilder instead of this class.
 */
abstract class Contenido_UrlBuilder extends cUriBuilder {

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilder instead of this function
     */
    private function __construct() {
        cDeprecated('use constructor from cUriBuilder instead');
        parent::__construct();
    }

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilder instead of this function
     */
    public static function getInstance() {
        cDeprecated('use getInstance() from cUriBuilder instead');
        cUriBuilder::getInstance();
    }

}

?>
