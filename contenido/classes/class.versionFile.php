<?php

################################################################################
# Old version of VersionFile class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.

/**
 * VersionFile
 * @deprecated  [2012-07-02] Use cVersionFile instead of this class.
 */
class VersionFile extends cVersionFile
{
    public function __construct($iIdOfType, $aFileInfo, $sFileName, $sTypeContent, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame, $sVersionFileName = '')
    {
        cDeprecated("Use class cVersionFile instead");
        parent::__construct($iIdOfType, $aFileInfo, $sFileName, $sTypeContent, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame, $sVersionFileName = '');
    }

}

?>