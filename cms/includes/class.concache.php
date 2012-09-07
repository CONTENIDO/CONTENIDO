<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * @class      cConCache
 * @brief      Class cConCache. Handles the "PEAR Cache Output" functionality.
 * @file       class.concache.php
 * @version    0.9
 * @date       2006-07-07
 * 
 * {@internal 
 *   created  2006-07-07
 *   modified 2008-07-03, bilal arslan, added security fix
 *
 *   $Id: class.concache.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class cConCache{

    /**
    * PEAR Cache Output Object
    *
    * @var obj $_oPearCache
    */
    var $_oPearCache;

    /**
    * Flag 2 activate caching.
    *
    * @var bool $_bEnableCaching
    */
    var $_bEnableCaching = false;

    /**
    * Flag for output of debug informations.
    *
    * @var bool $_bDebug
    */
    var $_bDebug = false;

    /**
    * Flag 2 print html comment including some debug informations.
    *
    * @var bool $_bHtmlComment
    */
    var $_bHtmlComment = false;

    /**
    * Start time of caching.
    *
    * @var int $_iStartTime
    */
    var $_iStartTime;

    /**
    * Option array 4 generating cache identifier (e. g. $_GET,$_POST, $_COOKIE, ...).
    *
    * @var array _aIDOptions
    */
    var $_aIDOptions;

    /**
    * Option array 4 pear caching.
    *
    * @var array $_aIDOptions
    */
    var $_aCacheOptions;

    /**
    * Handler array 2 store code, beeing executed on some events.
	* We have actually two events:
	* - 'beforeoutput': code to execute before doing the output
	* - 'afteroutput'   code to execute after output
    *
    * @var array $_aEventCode
    */
	var $_aEventCode;

    /**
    * Unique identifier for caching.
    *
    * @var string $_sID
    */
    var $_sID;

    /**
    * Directory 2 store cached output.
    *
    * @var string $_sDir
    */
    var $_sDir = 'cache/';

    /**
    * Subdirectory 2 store cached output.
    *
    * @var string $_sGroup
    */
    var $_sGroup = 'default';

    /**
    * Substring 2 add as prefix to cache-filename.
    *
    * @var string $_sPrefix
    */
    var $_sPrefix = 'cache_';

    /**
    * Default lifetime of cached files.
    *
    * @var int $_iLifetime
    */
    var $_iLifetime = 3600;

    /**
    * Used 2 store debug message.
    *
    * @var string $_sDebugMsg
    */
    var $_sDebugMsg = '';

    /**
    * HTML code template used for debug message.
    *
    * @var string $_sDebugTpl
    */
    var $_sDebugTpl = '<div>%s</div>';

    /**
    * HTML comment template used for generating some debug infos.
    *
    * @var string $_sDebugTpl
    */
    var $_sHtmlCommentTpl = '
<!--
CACHESTATE:  %s
TIME:        %s
VALID UNTIL: %s
-->
';

    /**
    * Constructor of cConCache
    *
    * @param   string   $cachedir      Directory 2 cache files
    * @param   string   $cachegroup    Subdirectory 2 cache files
    * @param   string   $cacheprefix   Prefixname 2 add 2 cached files
    */
    function cConCache($cachedir=null, $cachegroup=null, $cacheprefix=null){
        // wherever you want the cache files
        if(!is_null($cachedir)){
            $this->_sDir = $cachedir;
        }

        // subdirectory where you want the cache files
        if(!is_null($cachegroup)){
            $this->_sGroup = $cachegroup;
        }

        // optional a filename prefix
        if(!is_null($cacheprefix)){
            $this->_sPrefix = $cacheprefix;
        }

        // config options are passed to the cache as an array
        $this->_aCacheOptions = array('cache_dir' => $this->_sDir, 'filename_prefix' => $this->_sPrefix);

    } // function cConCache()


    /**
    * Set/Get the flag 2 enable caching.
    *
    * @param    bool   $enable   True 2 enable chaching or false
    * @return   mixed            Enable flag or void
    */
    function enable($enable=null){
        if(!is_null($enable) && is_bool($enable)){
            $this->_bEnableCaching = $enable;
        } else {
            return $this->_bEnableCaching;
        }
    } // function enable()


    /**
    * Set/Get the flag 2 debug cache object (prints out miss/hit state with execution time).
    *
    * @param    bool   $debug   True 2 activate debugging or false.
    * @return   mixed           Debug flag or void
    */
    function debug($debug){
        if(!is_null($debug) && is_bool($debug)){
            $this->_bDebug = $debug;
        } else {
            return $this->_bDebug;
        }
    } // function debug()


    /**
    * Set/Get flag 2 print out cache info as html comment.
    *
    * @param    bool   $htmlcomment   True debugging or false.
    * @return   void                  Htmlcomment flag or void
    */
    function htmlComment($htmlcomment){
        if(!is_null($htmlcomment) && is_bool($htmlcomment)){
            $this->_bHtmlComment = $htmlcomment;
        } else {
            return $this->_bHtmlComment;
        }
    } // function htmlComment()


    /**
    * Set/Get caching lifetime in seconds.
    *
    * @param    int   $seconds   New Lifetime in seconds
    * @return   mixed            Actual lifetime or void
    */
    function lifetime($seconds=null){
        if ($seconds != null && is_numeric($seconds) && $seconds > 0) {
            $this->_iLifetime = $seconds;
        } else {
            return $this->_iLifetime;
        }
    } // function lifetime()


    /**
    * Set/Get template to use on printing the chache info.
    *
    * @param    string   $template   Template string including the '%s' format definition.
    * @return   void
    */
    function infoTemplate($template){
        $this->_sDebugTpl = $template;
    } // function infoTemplate()


    /**
    * Add option 4 caching (e. g. $_GET,$_POST, $_COOKIE, ...). Used 2 generate the id for caching.
    *
    * @param    string   $name     Name of option
    * @param    string   $option   Value of option (any variable)
    * @return   void
    */
    function addOption($name, $option){
        $this->_aIDOptions[$name] = $option;
    } // function addOption()


    /**
    * Returns information cache hit/miss and execution time if caching is enabled.
    *
    * @return   string   Information about cache if caching is enabled, otherwhise nothing.
    */
    function getInfo(){
        if(!$this->_bEnableCaching){ return; }
        return $this->_sDebugMsg;
    } // function getInfo()


    /**
    * Handles PEAR caching. The script will be terminated by calling die(), if any cached 
    * content is found.
    *
    * @param    int    $iPageStartTime   Optional start time, e. g. start time of main script
    * @return   void
    */
    function start($iPageStartTime=null){
        if(!$this->_bEnableCaching){ return; }

        $this->_iStartTime = $this->_getMicroTime();

        // set cache object and unique id
        $this->_initPEARCache();

        // check if it's cached and start the output buffering if neccessary
        if ($content = $this->_oPearCache->start($this->_sID, $this->_sGroup)) {

			//raise beforeoutput event
			$this->_raiseEvent('beforeoutput');

			$iEndTime = $this->_getMicroTime();
            if ($this->_bHtmlComment) {
                $time     = sprintf("%2.4f", $iEndTime - $this->_iStartTime);
                $exp      = date('Y-m-d H:i:s', $this->_oPearCache->container->expires);
                $content .= sprintf($this->_sHtmlCommentTpl, 'HIT', $time.' sec.', $exp);
                if ($iPageStartTime != null && is_numeric($iPageStartTime)) {
                    $content .= '<!-- ['.sprintf("%2.4f", $iEndTime - $iPageStartTime).'] -->';
                }
            }

            if ($this->_bDebug) {
                $info = sprintf("HIT: %2.4f sec.", $iEndTime - $this->_iStartTime);
                $info = sprintf($this->_sDebugTpl, $info);
                $content = str_ireplace('</body>', $info."\n</body>", $content);
            }

            echo $content;

			//raise afteroutput event
			$this->_raiseEvent('afteroutput');

			die();
        }
    } // function start()


    /**
    * Handles ending of PEAR caching.
    *
    * @return   void
    */
    function end(){
        if (!$this->_bEnableCaching){ return; }

        // this might go into your auto_append file. store the data into the cache, default lifetime is set in $this->_iLifetime
        $this->_oPearCache->endPrint($this->_iLifetime, __FILE__ . ' ' . filemtime(__FILE__));

        if ($this->_bDebug) {
            $this->_sDebugMsg .= "\n".sprintf("MISS: %2.4f sec.\n", $this->_getMicroTime() - $this->_iStartTime);
            $this->_sDebugMsg  = sprintf($this->_sDebugTpl, $this->_sDebugMsg);
        }
    } // function end()


    /**
    * Removes any cached content if exists. 
    * This is nesessary to delete cached articles, if they are changed on backend.
    *
    * @return   void
    */
    function removeFromCache(){
        // set cache object and unique id
        $this->_initPEARCache();
        $bExists = $this->_oPearCache->isCached($this->_sID, $this->_sGroup);
        if ($bExists) {
            $this->_oPearCache->remove($this->_sID, $this->_sGroup);
        }
    } // function removeFromCache()


    /*
    * Creates one-time a instance of PEAR cache output object and also the unique id,
    * if propery $this->_oPearCache is not set.
    *
    * @return   void
    * @access   private
    */
    function _initPEARCache(){
        if (is_object($this->_oPearCache)) {
            return;
        }

        cInclude('pear', 'Cache/Output.php');

        // create a output cache object mode - file storage
        $this->_oPearCache = new Cache_Output('file', $this->_aCacheOptions);

        // generate an ID from whatever might influence the script behavoiur
        $this->_sID = $this->_oPearCache->generateID($this->_aIDOptions);
    } // function _initPEARCache()


	/**
	* Raises any defined event code by using eval().
    *
	* @param    string    $name   Name of event 2 raise
    * @return   void
    * @access   private
	*/
	function _raiseEvent($name){
		// check if event exists, get out if not
		if (!isset($this->_aEventCode[$name]) && !is_array($this->_aEventCode[$name])) {
			return;
		}

		// loop array and execute each defined php-code
		foreach ($this->_aEventCode[$name] as $code) {
			eval($code);
		}

	} // function _raiseEvent()


    /**
    * Returns microtime (Unix-Timestamp), used to calculate time of execution.
    *
    * @return   float   Timestamp
    * @access   private
    */
    function _getMicroTime(){
        $mtime = explode(' ', microtime());
        $mtime = $mtime[1] + $mtime[0];
        return $mtime;
    } // function _getMicroTime()

} // class cConCache



/**
* @class     cConCacheHandler
* @brief     Class cConCacheHandler. This is used to set configuration
*            and to manage caching output
* @version   0.9
* @date      07.07.2006
* @author    Murat Purc <murat@purc.de>
* @copyright © Murat Purc 2006
*/
class cConCacheHandler extends cConCache {

    /**
    * Constructor of cConCacheHandler.
    * Does some checks and sets the configuration of cache object.
    *
    * @param   array    $aConf         Configuration of caching as follows:
    *                                  - $a['excludecontenido'] bool. don't cache output, if we have a contenido variable, 
    *                                                                 e. g. on calling frontend preview from backend
    *                                  - $a['enable'] bool. activate caching of frontend output
    *                                  - $a['debug'] bool. compose debuginfo (hit/miss and execution time of caching)
    *                                  - $a['infotemplate'] string. debug information template
    *                                  - $a['htmlcomment'] bool. add a html comment including several debug messages to output
    *                                  - $a['lifetime'] int. lifetime in seconds 2 cache output
    *                                  - $a['cachedir'] string. directory where cached content is 2 store.
    *                                  - $a['cachegroup'] string. cache group, will be a subdirectory inside cachedir
    *                                  - $a['cacheprefix'] string. add prefix 2 stored filenames
    *                                  - $a['idoptions'] array. several variables 2 create a unique id, if the output depends 
    *                                                           on them. e. g. array('uri'=>$_SERVER['REQUEST_URI'],'post'=>$_POST,'get'=>$_GET);
    * @param   obj      $db            Reference 2 Contenido database object
    * @param   int      $iCreateCode   Flag of createcode state from table con_cat_art
    */
    function cConCacheHandler($aConf, &$db, $iCreateCode=null) {

        // check if caching is allowed on contenido variable
        if ($aConf['excludecontenido'] == true) {
            if (isset($GLOBALS['contenido'])) {
                // contenido variable exists, set state and get out here
                $this->_bEnableCaching = false;
                return;
            }
        }

        // set enable state of caching
        if (is_bool($aConf['enable'])) {
            $this->_bEnableCaching = $aConf['enable'];
        }
        if ($this->_bEnableCaching == false) {
            return;
        }

		// check if current article shouldn't be cached (by stese)
		$sExcludeIdarts = getEffectiveSetting('cache', 'excludeidarts', false);
		if ($sExcludeIdarts && strlen($sExcludeIdarts)>0) {
			$sExcludeIdarts = preg_replace("/[^0-9,]/", '', $sExcludeIdarts);
			$aExcludeIdart  = explode(',', $sExcludeIdarts);
			if (in_array($GLOBALS['idart'], $aExcludeIdart)) {
				$this->_bEnableCaching = false;
				return;
			}
		}

		$this->_oDB = $db;

        // set caching configuration
        parent::cConCache($aConf['cachedir'], $aConf['cachegroup']);
        $this->debug($aConf['debug']);
        $this->htmlComment($aConf['htmlcomment']);
        $this->lifetime($aConf['lifetime']);
        $this->infoTemplate($aConf['infotemplate']);
        foreach ($aConf['idoptions'] as $name => $var) {
            $this->addOption($name, $var);
        }

		if (is_array($aConf['raiseonevent'])) {
			$this->_aEventCode = $aConf['raiseonevent'];
		}

        // check, if code is to create
        $this->_bEnableCaching = !$this->_isCode2Create($iCreateCode);
        if ($this->_bEnableCaching == false) {
            $this->removeFromCache();
        }

    } // function cConCacheHandler()


    /**
    * Checks, if the create code flag is set. Output will be loaded from cache, if no code is 2 create.
    * It also checks the state of global variable $force.
    *
    * @param    mixed   $iCreateCode   State of create code (0 or 1). The state will be loaded from database if value is "null"
    * @return   bool                   True if code is to create, otherwhise false.
    * @access   private
    */
    function _isCode2Create($iCreateCode){
        if ($this->_bEnableCaching == false) {
            return;
        }

        // check content of global variable $force, get out if is's set to '1'
        if (isset($GLOBALS['force']) && is_numeric($GLOBALS['force']) && $GLOBALS['force'] == 1) {
			return true;
        }

		if (is_null($iCreateCode)) {
            // check if code is expired

			cInclude('classes', 'contenido/class.article.php');
			cInclude('classes', 'contenido/class.categoryarticle.php');

			$oApiCatArtColl = new cApiCategoryArticleCollection('idart="'.$GLOBALS['idart'].'" AND idcat="'.$GLOBALS['idcat'].'"');
			if ($oApiCatArt = $oApiCatArtColl->next()) {
				$iCreateCode = $oApiCatArt->get('createcode');
				unset($oApiCatArt);
			}
			unset($oApiCatArtColl);
        }

        return ($iCreateCode == 1) ? true : false;
    } // function _isCode2Create()


} // class cConCacheHandler

?>