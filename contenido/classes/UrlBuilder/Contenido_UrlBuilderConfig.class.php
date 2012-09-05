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
 * @deprecated [2012-09-04] Use cUriBuilderConfig instead of this class.
 */
class Contenido_UrlBuilderConfig extends cUriBuilderConfig {

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    private function __construct() {
        cDeprecated("Use class cVersionImport instead");
        parent::__construct();
    }

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    public static function setConfig(array $cfg) {
        cDeprecated("This function is not supported any longer");
        return cUriBuilderConfig::setConfig($cfg);
    }

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    public static function getUrlBuilderName() {
        cDeprecated("This function is not supported any longer");
        return cUriBuilderConfig::getUriBuilderName();
    }

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    public static function getConfig() {
        cDeprecated("This function is not supported any longer");
        return cUriBuilderConfig::getConfig();
    }

}

?>
