<?php
/**
* $RCSfile$
*
* Description: Object to build a Contenido Frontend Navigation 
*
* @version 0.1.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-15
* 
* @requires DebuggerFactory
* @requires Contenido_Category, Contenido_Categories for concrete implementations.
* }}
*
* $Id$
*/

cInclude('classes', 'Debug/DebuggerFactory.class.php');
cInclude('classes', 'Contenido_Category/Contenido_Category.class.php');

class Contenido_Frontend_Navigation_Base {
    /**#@+
     * @var int
     * @access protected
     */
    protected $iLang;
    protected $iClient;
    /**#@-*/
    
    /**
     * @var array
     * @access protected
     */
    protected $aCategories;
    
    /**
     * @var obj
     * @access protected
     */
    protected $oCategories;
    
    // needed properties for db queries
    /**
     * @var obj
     * @access protected
     */
    protected $oDb;
    /**
     * @var array
     * @access protected
     */
    protected $aCfg;
    /**
     * @var array
     * @access protected
     */
    protected $aCfgClient;
    /**
     * @var boolean
     * @access protected
     */
    protected $bDbg;
    /**
     * @var string
     * @access protected
     */
    protected $sDbgMode;
    /**
     * @var obj
     * @access protected
     */
    protected $oDbg;
    
    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @param int $iClient
     * @param int $iLang
     * @return void
     * @author Rudi Bieller
     */
    public function __construct(DB_Contenido $oDb, array $aCfg, $iClient, $iLang, array $aCfgClient) {
        $this->oDb = $oDb;
        $this->aCfg = $aCfg;
        $this->iClient = (int) $iClient;
        $this->iLang = (int) $iLang;
        $this->aCfgClient = $aCfgClient;
        $this->_iCurrentLoadDepth = 1;
        $this->_aSubCategories = array();
        $this->bDbg = false;
        $this->oDbg = null;
    }
    
    /**
     * Get a URL to a Navigation point.
     * Depending on style of URL needed, values of $aParams differ.
     * @access public
     * @param array $aParams Parameters needed to build the URL
     * @param string $sStyle Available styles are: front_content, custom, custom_path
     * @param array $aConfig As default this is Contenido_UrlBuilderConfig::getConfig(), can be overridden by setting this value
     * @param boolean $bUseAbsolutePath If true, will use absolute http://www.xy.com/ as "prefix"
     * @return void
     * @throws InvalidArgumentException
     * @see appropriate Contenido_UrlBuilder for details on needed params
     * @todo Apply other styles as soon as they are available
     */
    public function getUrl(array $aParams, $sStyle = 'custom_path', array $aConfig = array(), $bUseAbsolutePath = false) {
        cInclude('classes', 'UrlBuilder/Contenido_UrlBuilderFactory.class.php');
        try {
            $oUrlBuilder = Contenido_UrlBuilderFactory::getUrlBuilder($sStyle);
            if ($bUseAbsolutePath === true) {
                $oUrlBuilder->setHttpBasePath($this->aCfgClient[$this->iClient]['path']['htmlpath']);
            }
            $oUrlBuilder->buildUrl($aParams, $bUseAbsolutePath, $aConfig);
            return $oUrlBuilder->getUrl();
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
    }
    
    /**
     * Set internal property for debugging on/off and choose appropriate debug object
     * @access public
     * @param boolean $bDebug
     * @param string $sDebugMode
     * @return void
     * @author Rudi Bieller
     */
    public function setDebug($bDebug = true, $sDebugMode = 'visible') {
        if (!in_array($sDebugMode, array('visible', 'hidden'))) {
            $sDebugMode = 'hidden';
        }
        $this->sDbgMode = $sDebugMode;
        if ($bDebug === true) {
            $this->bDbg = true;
            $this->oDbg = DebuggerFactory::getDebugger($sDebugMode);
        } else {
            $this->bDbg = false;
            $this->oDbg = null;
        }
    }
}
?>