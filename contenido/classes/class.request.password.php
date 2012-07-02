<?php

################################################################################
# Old version of RequestPassword class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.

/**
 * RequestPassword
 * @deprecated  [2012-07-02] Use cRequestPassword instead of this class.
 */
class RequestPassword extends cPasswordRequest
{
    public function __construct($oDb, $aCfg)
    {
        cDeprecated("Use class cPasswordRequest instead");
        parent::__construct($oDb, $aCfg);
    }
    public function RequestPassword($oDb, $aCfg)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($oDb, $aCfg);
    }
}

?>