<?php

/**
 * This file contains the output cache classes.
 *
 * @package Core
 * @subpackage Cache
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the CONTENIDO output cache.
 *
 * @package Core
 * @subpackage Cache
 */
class cOutputCache {

    /**
     * File cache object.
     *
     * @var cFileCache
     */
    protected $_fileCache;

    /**
     * Flag to activate caching.
     *
     * @var bool
     */
    protected $_bEnableCaching = false;

    /**
     * Flag for output of debug informations.
     *
     * @var bool
     */
    protected $_bDebug = false;

    /**
     * Flag to print html comment including some debug informations.
     *
     * @var bool
     */
    protected $_bHtmlComment = false;

    /**
     * Start time of caching.
     *
     * @var int
     */
    protected $_iStartTime;

    /**
     * Option array for generating cache identifier
     * (e.g. $_GET,$_POST, $_COOKIE, ...).
     *
     * @var array
     */
    protected $_aIDOptions;

    /**
     * Option array for pear caching.
     *
     * @var array
     */
    protected $_aCacheOptions;

    /**
     * Handler array to store code, beeing executed on some hooks.
     * We have actually two hooks:
     * - 'beforeoutput': code to execute before doing the output
     * - 'afteroutput' code to execute after output
     *
     * @var array
     */
    protected $_aEventCode;

    /**
     * Unique identifier for caching.
     *
     * @var string
     */
    protected $_sID;

    /**
     * Directory to store cached output.
     *
     * @var string
     */
    protected $_sDir = 'cache/';

    /**
     * Subdirectory to store cached output.
     *
     * @var string
     */
    protected $_sGroup = 'default';

    /**
     * Substring to add as prefix to cache-filename.
     *
     * @var string
     */
    protected $_sPrefix = 'cache_output_';

    /**
     * Default lifetime of cached files.
     *
     * @var int
     */
    protected $_iLifetime = 3600;

    /**
     * Used to store debug message.
     *
     * @var string
     */
    protected $_sDebugMsg = '';

    /**
     * HTML code template used for debug message.
     *
     * @var string
     */
    protected $_sDebugTpl = '<div>%s</div>';

    /**
     * HTML comment template used for generating some debug infos.
     *
     * @var string
     */
    protected $_sHtmlCommentTpl = '
<!--
CACHESTATE:  %s
TIME:        %s
VALID UNTIL: %s
-->
';

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $cachedir [optional]
     *         Directory to cache files
     * @param string $cachegroup [optional]
     *         Subdirectory to cache files
     * @param string $cacheprefix [optional]
     *         Prefixname to add to cached files
     */
    public function __construct($cachedir = NULL, $cachegroup = NULL, $cacheprefix = NULL) {
        // wherever you want the cache files
        if (!is_null($cachedir)) {
            $this->_sDir = $cachedir;
        }

        // subdirectory where you want the cache files
        if (!is_null($cachegroup)) {
            $this->_sGroup = $cachegroup;
        }

        // optional a filename prefix
        if (!is_null($cacheprefix)) {
            $this->_sPrefix = $cacheprefix;
        }

        // config options are passed to the cache as an array
        $this->_aCacheOptions = array(
            'cacheDir' => $this->_sDir,
            'fileNamePrefix' => $this->_sPrefix
        );
    }

    /**
     * Get/Set the flag to enable caching.
     *
     * @param bool $enable [optional]
     *         True to enable caching or false
     * @return mixed
     *         Enable flag or void
     */
    public function enable($enable = NULL) {
        if (!is_null($enable) && is_bool($enable)) {
            $this->_bEnableCaching = $enable;
        } else {
            return $this->_bEnableCaching;
        }
    }

    /**
     * Get/Set the flag to debug cache object (prints out miss/hit state
     * with execution time).
     *
     * @param bool $debug
     *         True to activate debugging or false.
     * @return mixed
     *         Debug flag or void
     */
    public function debug($debug) {
        if (!is_null($debug) && is_bool($debug)) {
            $this->_bDebug = $debug;
        } else {
            return $this->_bDebug;
        }
    }

    /**
     * Get/Set flag to print out cache info as html comment.
     *
     * @param bool $htmlcomment
     *         True debugging or false.
     * @return string
     *         Htmlcomment flag or void
     */
    public function htmlComment($htmlcomment) {
        if (!is_null($htmlcomment) && is_bool($htmlcomment)) {
            $this->_bHtmlComment = $htmlcomment;
        } else {
            return $this->_bHtmlComment;
        }
    }

    /**
     * Get/Set caching lifetime in seconds.
     *
     * @param int $seconds [optional]
     *         New Lifetime in seconds
     * @return mixed
     *         Actual lifetime or void
     */
    public function lifetime($seconds = NULL) {
        if ($seconds != NULL && is_numeric($seconds) && $seconds > 0) {
            $this->_iLifetime = $seconds;
        } else {
            return $this->_iLifetime;
        }
    }

    /**
     * Get/Set template to use on printing the chache info.
     *
     * @param string $template
     *         Template string including the '%s' format definition.
     */
    public function infoTemplate($template) {
        $this->_sDebugTpl = $template;
    }

    /**
     * Add option for caching (e.g. $_GET,$_POST, $_COOKIE, ...).
     *
     * Used to generate the id for caching.
     *
     * @param string $name
     *         Name of option
     * @param string $option
     *         Value of option (any variable)
     */
    public function addOption($name, $option) {
        $this->_aIDOptions[$name] = $option;
    }

    /**
     * Returns information cache hit/miss and execution time if caching
     * is enabled.
     *
     * @return string
     *         Information about cache if caching is enabled, otherwise nothing.
     */
    public function getInfo() {
        if ($this->_bEnableCaching) {
            return $this->_sDebugMsg;
        }
    }

    /**
     * Starts the cache process.
     *
     * @return bool|string
     * 
     * @throws cInvalidArgumentException
     */
    protected function _start() {
        $id = $this->_sID;
        $group = $this->_sGroup;

        // this is already cached return it from the cache so that the
        // user can use the cache content and stop script execution
        if ($content = $this->_fileCache->get($id, $group)) {
            return $content;
        }

        // WARNING: we need the output buffer - possible clashes
        ob_start();
        ob_implicit_flush(false);

        return '';
    }

    /**
     * Handles PEAR caching.
     *
     * The script will be terminated by calling die(), if any cached
     * content is found.
     *
     * @param int $iPageStartTime [optional]
     *                            Optional start time, e.g. start time of main script
     *
     * @throws cInvalidArgumentException
     */
    public function start($iPageStartTime = NULL) {
        if (!$this->_bEnableCaching) {
            return;
        }

        $this->_iStartTime = $this->_getMicroTime();

        // set cache object and unique id
        $this->_initFileCache();

        // check if it's cached and start the output buffering if necessary
        if ($content = $this->_start()) {
            // raise beforeoutput event
            $this->_raiseEvent('beforeoutput');

            $iEndTime = $this->_getMicroTime();
            if ($this->_bHtmlComment) {
                $time = sprintf("%2.4f", $iEndTime - $this->_iStartTime);
                $exp = ($this->_iLifetime == 0? 'infinite' : date('Y-m-d H:i:s', time() + $this->_iLifetime));
                $content .= sprintf($this->_sHtmlCommentTpl, 'HIT', $time . ' sec.', $exp);
                if ($iPageStartTime != NULL && is_numeric($iPageStartTime)) {
                    $content .= '<!-- [' . sprintf("%2.4f", $iEndTime - $iPageStartTime) . '] -->';
                }
            }

            if ($this->_bDebug) {
                $info = sprintf("HIT: %2.4f sec.", $iEndTime - $this->_iStartTime);
                $info = sprintf($this->_sDebugTpl, $info);
                $content = str_ireplace('</body>', $info . "\n</body>", $content);
            }

            echo $content;

            // raise afteroutput event
            $this->_raiseEvent('afteroutput');

            die();
        }
    }

    /**
     * Handles ending of PEAR caching.
     *
     * @throws cInvalidArgumentException
     */
    public function end() {
        if (!$this->_bEnableCaching) {
            return;
        }

        $content = ob_get_contents();
        ob_end_clean();

        $this->_fileCache->save($content, $this->_sID, $this->_sGroup);

        echo $content;

        if ($this->_bDebug) {
            $this->_sDebugMsg .= "\n" . sprintf("MISS: %2.4f sec.\n", $this->_getMicroTime() - $this->_iStartTime);
            $this->_sDebugMsg = sprintf($this->_sDebugTpl, $this->_sDebugMsg);
        }
    }

    /**
     * Removes any cached content if exists.
     *
     * This is nesessary to delete cached articles, if they are changed on
     * backend.
     *
     * @throws cInvalidArgumentException
     */
    public function removeFromCache() {
        // set cache object and unique id
        $this->_initFileCache();
        $this->_fileCache->remove($this->_sID, $this->_sGroup);
    }

    /**
     * Creates one-time a instance of PEAR cache output object and also
     * the unique id, if propery $this->_oPearCache is not set.
     */
    protected function _initFileCache() {
        if (is_object($this->_fileCache)) {
            return;
        }

        // create an output cache object mode - file storage
        $this->_fileCache = new cFileCache($this->_aCacheOptions);

        // generate an ID from whatever might influence the script behaviour
        $this->_sID = $this->_fileCache->generateID($this->_aIDOptions);
    }

    /**
     * Raises any defined event code by using eval().
     *
     * @param string $name
     *         Name of event to raise
     */
    protected function _raiseEvent($name) {
        // skip if event does not exist
        if (!isset($this->_aEventCode[$name]) && !is_array($this->_aEventCode[$name])) {
            return;
        }

        // loop array and execute each defined php-code
        foreach ($this->_aEventCode[$name] as $code) {
            eval($code);
        }
    }

    /**
     * Returns microtime (UNIX timestamp), used to calculate time of execution.
     *
     * @return float
     *         Timestamp
     */
    protected function _getMicroTime() {
        $mtime = explode(' ', microtime());
        $mtime = $mtime[1] + $mtime[0];

        return $mtime;
    }
}

/**
 * This class contains functions for the output cache handler in CONTENIDO.
 *
 * @package Core
 * @subpackage Cache
 */
class cOutputCacheHandler extends cOutputCache
{
    /**
     * Constructor to create an instance of this class.
     *
     * Does some checks and sets the configuration of cache object.
     *
     * @param array $aConf
     *                           Configuration of caching as follows:
     *                           - $a['excludecontenido'] bool
     *                           don't cache output, if we have a CONTENIDO variable,
     *                           e.g. on calling frontend preview from backend
     *                           - $a['enable'] bool
     *                           activate caching of frontend output
     *                           - $a['debug'] bool
     *                           compose debuginfo (hit/miss and execution time of caching)
     *                           - $a['infotemplate'] string
     *                           debug information template
     *                           - $a['htmlcomment'] bool
     *                           add a html comment including several debug messages to output
     *                           - $a['lifetime'] int
     *                           lifetime in seconds to cache output
     *                           - $a['cachedir'] string
     *                           directory where cached content is to store.
     *                           - $a['cachegroup'] string
     *                           cache group, will be a subdirectory inside cachedir
     *                           - $a['cacheprefix'] string
     *                           add prefix to stored filenames
     *                           - $a['idoptions'] array
     *                           several variables to create a unique id,
     *                           if the output depends on them. e.g.
     *                           array(
     *                           'uri' => $_SERVER['REQUEST_URI'],
     *                           'post' => $_POST, 'get' => $_GET
     *                           )
     * @param cDb   $db
     *                           CONTENIDO database object
     * @param int   $iCreateCode [optional]
     *                           Flag of createcode state from table con_cat_art
     *                           
     * @throws cDbException
     * @throws cException
     */
    public function __construct($aConf, $db, $iCreateCode = NULL) {
        // check if caching is allowed on CONTENIDO variable
        if ($aConf['excludecontenido'] == true) {
            if (isset($GLOBALS['contenido'])) {
                // CONTENIDO variable exists, set state and get out here
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
        if ($sExcludeIdarts && cString::getStringLength($sExcludeIdarts) > 0) {
            $sExcludeIdarts = preg_replace("/[^0-9,]/", '', $sExcludeIdarts);
            $aExcludeIdart = explode(',', $sExcludeIdarts);
            if (in_array($GLOBALS['idart'], $aExcludeIdart)) {
                $this->_bEnableCaching = false;

                return;
            }
        }

        $this->_oDB = $db;

        // set caching configuration
        parent::__construct($aConf['cachedir'], $aConf['cachegroup']);
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
    }

    /**
     * Checks, if the create code flag is set.
     * Output will be loaded from cache, if no code is to create.
     * It also checks the state of global variable $force.
     *
     * @param mixed $iCreateCode
     *         State of create code (0 or 1).
     *         The state will be loaded from database if value is NULL
     *
     * @return bool
     *         True if code is to create, otherwise false.
     * 
     * @throws cDbException
     * @throws cException
     */
    protected function _isCode2Create($iCreateCode) {
        if ($this->_bEnableCaching == false) {
            return;
        }

        // check content of global variable $force, get out if is's set to '1'
        if (isset($GLOBALS['force']) && is_numeric($GLOBALS['force']) && $GLOBALS['force'] == 1) {
            return true;
        }

        if (is_null($iCreateCode)) {
            // check if code is expired

            $oApiCatArtColl = new cApiCategoryArticleCollection('idart="' . $GLOBALS['idart'] . '" AND idcat="' . $GLOBALS['idcat'] . '"');
            if ($oApiCatArt = $oApiCatArtColl->next()) {
                $iCreateCode = $oApiCatArt->get('createcode');
                unset($oApiCatArt);
            }
            unset($oApiCatArtColl);
        }

        return ($iCreateCode == 1) ? true : false;
    }

}
