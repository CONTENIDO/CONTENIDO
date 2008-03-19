<?php
/**
* $RCSfile$
*
* Description: Abstract implementation of Contenido_UrlBuilder.
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-21
* }}
*
* $Id$
*/

abstract class Contenido_UrlBuilder {
    /**
     * @var string
     * @access protected
     * @desc Holds final value of built URL
     */
    protected $sUrl; // needed in this context
    /**
     * @var string
     * @access protected
     * @desc Holds URL that is used as base for an absolute path, e.g. http://contenido.org/
     */
    protected $sHttpBasePath; // needed in this context
    
    /**
     * Implementation of Singleton. Get instance of concrete Contenido_UrlBuilder_XYZ
     * @access public
     * @return obj Contenido_UrlBuilder_Frontcontent
     * @author Rudi Bieller
     */
    abstract public static function getInstance();
    
    /**
     * Set http base path, e.g. http://contenido.org/
     * @access public
     * @return void
     * @author Rudi Bieller
     */
    public function setHttpBasePath($sBasePath) {
        $this->sHttpBasePath = (string) $sBasePath;
    }
    
    /**
     * Builds a URL in index-a-1.html style.
     * Index keys of $aParams will be used as "a", corresponding values as "1" in this sample.
     *
     * @param array $aParams
     * @param boolean $bUseAbsolutePath
     * @param string $sSeparator
     * @return void
     * @throws InvalidArgumentException
     * @author Rudi Bieller
     */
    abstract public function buildUrl(array $aParams, $bUseAbsolutePath = false);
    
    /**
     * Return built URL
     * @access public
     * @return string
     * @author Rudi Bieller
     */
    public function getUrl() {
        return (string) $this->sUrl;
    }
}
?>