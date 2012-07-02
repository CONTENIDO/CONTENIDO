<?php

################################################################################
# Old version of VersionImport class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.

/**
 * VersionImport
 * @deprecated  [2012-07-02] Use cVersionImport instead of this class.
 */
class VersionImport extends cVersionImport
{
    public function __construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame)
    {
        cDeprecated("Use class cVersionImport instead");
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
    }

}

?>