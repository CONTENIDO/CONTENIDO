<?php

################################################################################
# Old version of VersionModule class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.

/**
 * VersionModule
 * @deprecated  [2012-07-02] Use cVersionModule instead of this class.
 */
class VersionModule extends cVersionModule
{
    public function __construct($iIdMod, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame)
    {
        cDeprecated("Use class cVersionModule instead");
        parent::__construct($iIdMod, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
    }

}

?>