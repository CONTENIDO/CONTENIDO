<?php

################################################################################
# Old version of VersionLayout class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.

/**
 * VersionLayout
 * @deprecated  [2012-07-02] Use cVersionLayout instead of this class.
 */
class VersionLayout extends cVersionLayout
{
    public function __construct($iIdLayout, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame)
    {
        cDeprecated("Use class cVersionLayout instead");
        parent::__construct($iIdLayout, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
    }

}

?>