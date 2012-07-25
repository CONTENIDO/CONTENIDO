<?php

################################################################################
# Old version of Version class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.

/**
 * Version
 * @deprecated  [2012-07-02] Use cVersion instead of this class.
 */
class Version extends cVersion
{
    public function __construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame)
    {
        cDeprecated("Use class cVersion instead");
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
    }

}

?>