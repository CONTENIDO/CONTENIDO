<?php

// ##############################################################################
// Old version of VersionImport class
//
// NOTE: Class implemetation below is deprecated and the will be removed in
// future versions of contenido.
// Don't use it, it's still available due to downwards compatibility.

/**
 * VersionImport
 *
 * @deprecated [2012-09-04] Use cUriBuilderFactory instead of this class.
 */
class Contenido_UrlBuilderFactory extends cUriBuilderFactory{

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderFactory instead of this
     *             function
     */
    public static function getUrlBuilder($sBuilder) {
        cDeprecated("This function is not supported any longer");
        cUriBuilderFactory::getUriBuilder($sBuilder);
    }

}

?>
