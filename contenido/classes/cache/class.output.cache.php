<?php

/**
 * This file contains the output cache classes.
 *
 * @package    Core
 * @subpackage Cache
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the CONTENIDO output cache.
 *
 * @package    Core
 * @subpackage Cache
 */
class cOutputCache
{

    /**
     * File cache object.
     *
     * @var cFileCache
     */
    protected $_fileCache;

    /**
     * Database instance.
     *
     * @var cDb
     */
    protected $_oDB;

    /**
     * Flag to activate caching.
     *
     * @var bool
     */
    protected $_bEnableCaching = false;

    /**
     * Flag for output of debug information.
     *
     * @var bool
     */
    protected $_bDebug = false;

    /**
     * Flag to print html comment including some debug information.
     *
     * @var bool
     */
    protected $_bHtmlComment = false;

    /**
     * Start time of caching.
     *
     * @var float
     */
    protected $_fStartTime;

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
     * Handler array to store code, being executed on some hooks.
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
     * @param ?string $cacheDir [optional] Directory to cache files
     * @param ?string $cacheGroup [optional] Subdirectory to cache files
     * @param ?string $cachePrefix [optional] Prefix name to add to cached files
     */
    public function __construct(string $cacheDir = null, string $cacheGroup = null, string $cachePrefix = null)
    {
        // wherever you want the cache files
        if (!is_null($cacheDir)) {
            $this->_sDir = $cacheDir;
        }

        // subdirectory where you want the cache files
        if (!is_null($cacheGroup)) {
            $this->_sGroup = $cacheGroup;
        }

        // optional a filename prefix
        if (!is_null($cachePrefix)) {
            $this->_sPrefix = $cachePrefix;
        }

        // config options are passed to the cache as an array
        $this->_aCacheOptions = [
            'cacheDir' => $this->_sDir,
            'fileNamePrefix' => $this->_sPrefix,
        ];
    }

    /**
     * Get/Set the flag to enable caching.
     *
     * @param ?bool $enable [optional] True to enable caching or false
     * @return ?bool Enable flag or null
     */
    public function enable(?bool $enable = null): ?bool
    {
        if (is_bool($enable)) {
            $this->_bEnableCaching = $enable;
            return null;
        } else {
            return $this->_bEnableCaching;
        }
    }

    /**
     * Get/Set the flag to debug cache object (prints out miss/hit state with execution time).
     *
     * @param bool $debug True to activate debugging or false.
     * @return ?bool Debug flag or null
     */
    public function debug(?bool $debug = null): ?bool
    {
        if (is_bool($debug)) {
            $this->_bDebug = $debug;
            return null;
        } else {
            return $this->_bDebug;
        }
    }

    /**
     * Get/Set flag to print out cache info as html comment.
     *
     * @param ?bool $htmlcomment True debugging or false.
     * @return ?bool Htmlcomment flag or null
     */
    public function htmlComment(?bool $htmlcomment): ?bool
    {
        if (is_bool($htmlcomment)) {
            $this->_bHtmlComment = $htmlcomment;
            return null;
        } else {
            return $this->_bHtmlComment;
        }
    }

    /**
     * Get/Set caching lifetime in seconds.
     *
     * @param ?int $seconds [optional] New Lifetime in seconds
     * @return ?int Actual lifetime or null
     */
    public function lifetime(?int $seconds = null): ?int
    {
        if (is_numeric($seconds) && $seconds > 0) {
            $this->_iLifetime = $seconds;
            return null;
        } else {
            return $this->_iLifetime;
        }
    }

    /**
     * Get/Set template to use on printing the cache info.
     *
     * @param string $template Template string including the '%s' format definition.
     */
    public function infoTemplate(string $template)
    {
        $this->_sDebugTpl = $template;
    }

    /**
     * Add option for caching (e.g. $_GET,$_POST, $_COOKIE, ...).
     *
     * Used to generate the id for caching.
     *
     * @param string $name Name of option
     * @param mixed $value Value of option (any variable)
     */
    public function addOption(string $name, $value)
    {
        $this->_aIDOptions[$name] = $value;
    }

    /**
     * Returns information cache hit/miss and execution time if caching is enabled.
     *
     * @return ?string Information about cache if caching is enabled, otherwise null.
     */
    public function getInfo(): ?string
    {
        if ($this->_bEnableCaching) {
            return $this->_sDebugMsg;
        }

        return null;
    }

    /**
     * Starts the cache process.
     *
     * @return bool|string
     * @throws cInvalidArgumentException
     */
    protected function _start()
    {
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
     * The script will be terminated by calling die(), if any cached content is found.
     *
     * @param ?int $pageStartTime [optional] Optional start time, e.g. start time of main script
     * @throws cInvalidArgumentException
     */
    public function start(int $pageStartTime = null)
    {
        if (!$this->_bEnableCaching) {
            return;
        }

        $this->_fStartTime = $this->_getMicroTime();

        // set cache object and unique id
        $this->_initFileCache();

        // check if it's cached and start the output buffering if necessary
        if ($content = $this->_start()) {
            // raise beforeoutput event
            $this->_raiseEvent('beforeoutput');

            $fEndTime = $this->_getMicroTime();
            if ($this->_bHtmlComment) {
                $time = sprintf("%2.4f", $fEndTime - $this->_fStartTime);
                $exp = ($this->_iLifetime == 0 ? 'infinite' : date('Y-m-d H:i:s', time() + $this->_iLifetime));
                $content .= sprintf($this->_sHtmlCommentTpl, 'HIT', $time . ' sec.', $exp);
                if ($pageStartTime != null) {
                    $content .= '<!-- [' . sprintf("%2.4f", $fEndTime - $pageStartTime) . '] -->';
                }
            }

            if ($this->_bDebug) {
                $info = sprintf("HIT: %2.4f sec.", $fEndTime - $this->_fStartTime);
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
    public function end()
    {
        if (!$this->_bEnableCaching) {
            return;
        }

        $content = ob_get_contents();
        ob_end_clean();

        $this->_fileCache->save($content, $this->_sID, $this->_sGroup);

        echo $content;

        if ($this->_bDebug) {
            $this->_sDebugMsg .= "\n" . sprintf("MISS: %2.4f sec.\n", $this->_getMicroTime() - $this->_fStartTime);
            $this->_sDebugMsg = sprintf($this->_sDebugTpl, $this->_sDebugMsg);
        }
    }

    /**
     * Removes any cached content if exists.
     *
     * This is necessary to delete cached articles, if they are changed on backend.
     *
     * @throws cInvalidArgumentException
     */
    public function removeFromCache()
    {
        // set cache object and unique id
        $this->_initFileCache();
        $this->_fileCache->remove($this->_sID, $this->_sGroup);
    }

    /**
     * Creates one-time an instance of PEAR cache output object and also
     * the unique id, if proper $this->_oPearCache is not set.
     */
    protected function _initFileCache()
    {
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
     * @param string $name Name of event to raise
     */
    protected function _raiseEvent(string $name)
    {
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
     * @return float Timestamp
     */
    protected function _getMicroTime(): float
    {
        $mtime = explode(' ', microtime());
        return (float)$mtime[1] + (float)$mtime[0];
    }
}

/**
 * This class contains functions for the output cache handler in CONTENIDO.
 *
 * @package    Core
 * @subpackage Cache
 */
class cOutputCacheHandler extends cOutputCache
{
    /**
     * Constructor to create an instance of this class.
     *
     * Does some checks and sets the configuration of cache object.
     *
     * @param array $aConf Configuration of caching as follows:
     *      - $a['excludecontenido'] bool
     *        don't cache output, if we have a CONTENIDO variable,
     *        e.g. on calling frontend preview from backend
     *      - $a['enable'] bool
     *        activate caching of frontend output
     *      - $a['debug'] bool
     *        compose debugging information (hit/miss and execution time of caching)
     *      - $a['infotemplate'] string
     *        debug information template
     *      - $a['htmlcomment'] bool
     *        add a html comment including several debug messages to output
     *      - $a['lifetime'] int
     *        lifetime in seconds to cache output
     *      - $a['cachedir'] string
     *        directory where cached content is to store.
     *      - $a['cachegroup'] string
     *        cache group, will be a subdirectory inside the cache directory
     *      - $a['cacheprefix'] string
     *        add prefix to stored filenames
     *      - $a['idoptions'] array
     *        several variables to create a unique id,
     *        if the output depends on them. e.g.
     *        [
     *            'uri' => $_SERVER['REQUEST_URI'],
     *            'post' => $_POST, 'get' => $_GET
     *        ]
     * @param cDb $db CONTENIDO database object
     * @param ?int $createCode [optional] Flag of createcode state from table con_cat_art
     * @throws cDbException|cException
     */
    public function __construct(array $aConf, cDb $db, ?int $createCode = null)
    {
        // Check if caching is allowed in backend
        if ($aConf['excludecontenido'] && cRegistry::getBackendSessionId()) {
            // CONTENIDO session exists, set state and get out here
            $this->_bEnableCaching = false;
            return;
        }

        // Set enable state of caching
        if (is_bool($aConf['enable'])) {
            $this->_bEnableCaching = $aConf['enable'];
        }
        if (!$this->_bEnableCaching) {
            return;
        }

        // Check if current article shouldn't be cached (by stese)
        $sExcludeIdarts = getEffectiveSetting('cache', 'excludeidarts', false);
        if ($sExcludeIdarts && cString::getStringLength($sExcludeIdarts) > 0) {
            $sExcludeIdarts = preg_replace("/[^0-9,]/", '', $sExcludeIdarts);
            $aExcludeIdart = explode(',', $sExcludeIdarts);
            if (in_array(cRegistry::getArticleId(), $aExcludeIdart)) {
                $this->_bEnableCaching = false;
                return;
            }
        }

        $this->_oDB = $db;

        // Set caching configuration
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

        // Check, if code is to create
        $this->_bEnableCaching = !$this->_isCode2Create($createCode);
        if (!$this->_bEnableCaching) {
            $this->removeFromCache();
        }
    }

    /**
     * Checks, if the creation code flag is set.
     * Output will be loaded from cache, if no code is to create.
     * It also checks the state of global variable $force.
     *
     * @param mixed $createCode State of create code (0 or 1).
     *      The state will be loaded from database if value is null
     * @return bool True if code is to create, otherwise false.
     * @throws cDbException|cException
     */
    protected function _isCode2Create($createCode): bool
    {
        if (!$this->_bEnableCaching) {
            return false;
        }

        // check content of global variable $force, get out if it's set to '1'
        if (isset($GLOBALS['force']) && is_numeric($GLOBALS['force']) && $GLOBALS['force'] == 1) {
            return true;
        }

        if (is_null($createCode)) {
            // check if code is expired
            $sWhere = sprintf(
                '`idart` = %d AND `idcat` = %d',
                cRegistry::getArticleId(),
                cRegistry::getCategoryId()
            );
            $oApiCatArtColl = new cApiCategoryArticleCollection($sWhere);
            if ($oApiCatArt = $oApiCatArtColl->next()) {
                $createCode = $oApiCatArt->get('createcode');
                unset($oApiCatArt);
            }
            unset($oApiCatArtColl);
        }

        return $createCode == 1;
    }

}
