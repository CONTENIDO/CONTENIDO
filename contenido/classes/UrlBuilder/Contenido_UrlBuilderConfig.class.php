<?php

// ##############################################################################
// Old version of VersionImport class
//
// NOTE: Class implemetation below is deprecated and the will be removed in
// future versions of contenido.
// Don't use it, it's still available due to downwards compatibility.

/**
 * Contenido_UrlBuilderConfig
 *
 * @deprecated [2012-09-04] Use cUriBuilderConfig instead of this class.
 */
class Contenido_UrlBuilderConfig extends cUriBuilderConfig {

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    private function __construct() {
        cDeprecated('use constructor from cUriBuilderConfig instead');
        parent::__construct();
    }

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    public static function setConfig(array $cfg) {
        cDeprecated('use setConfig() from cUriBuilderConfig instead');
        return cUriBuilderConfig::setConfig($cfg);
    }

    /**
     *
     * @deprecated 2012-09-06 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    public static function getUrlBuilderName() {
        cDeprecated('use getInstance() from cUriBuilderConfig instead');
        return cUriBuilderConfig::getUriBuilderName();
    }

    /**
     *
     * @deprecated 2012-09-04 this function is not supported any longer
     *             use function located in cUriBuilderConfig instead of this
     *             function
     */
    public static function getConfig() {
        cDeprecated('use getConfig() instead, located in cUriBuilderConfig');
        return cUriBuilderConfig::getConfig();
    }

}

?>
