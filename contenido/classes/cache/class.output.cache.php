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
     * @var cFileCache File cache object.
     */
    protected $fileCache;

    /**
     * @var cDb Database instance.
     */
    protected $db;

    /**
     * @var bool Flag to activate caching.
     */
    protected $enable = false;

    /**
     * @var bool Flag for output of debug information.
     */
    protected $debug = false;

    /**
     * @var bool Flag to print HTML comment including some debug information.
     */
    protected $htmlComment = false;

    /**
     * @var float Start time of caching.
     */
    protected $startTime;

    /**
     * @var array Option array for generating cache identifier
     *      (e.g. $_GET,$_POST, $_COOKIE, ...).
     */
    protected $idOptions;

    /**
     * @var array Option array for caching.
     */
    protected $options;

    /**
     * Handler array to store code, being executed on some hooks.
     * We have actually two hooks:
     * - 'beforeoutput': code to execute before doing the output
     * - 'afteroutput' code to execute after output
     *
     * @var array
     */
    protected $eventCode;

    /**
     * @var string Unique identifier for caching.
     */
    protected $id;

    /**
     * @var string Directory to store cached output.
     */
    protected $dir = 'cache/';

    /**
     * @var string Subdirectory to store cached output.
     */
    protected $group = 'default';

    /**
     * @var string Substring to add as a prefix to cache-filename.
     */
    protected $prefix = 'cache_output_';

    /**
     * @var int Default lifetime of cached files.
     */
    protected $lifetime = 3600;

    /**
     * @var string Used to store a debug message.
     */
    protected $debugMsg = '';

    /**
     * @var string HTML code template used for a debug message.
     */
    protected $debugTpl = '<div>%s</div>';

    /**
     * @var string HTML comment template used for generating some debug infos.
     */
    protected $htmlCommentTpl = '
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
    public function __construct(
        ?string $cacheDir = null,
        ?string $cacheGroup = null,
        ?string $cachePrefix = null
        )
    {
        // wherever you want the cache files
        if (!is_null($cacheDir)) {
            $this->dir = $cacheDir;
        }

        // subdirectory where you want the cache files
        if (!is_null($cacheGroup)) {
            $this->group = $cacheGroup;
        }

        // optional a filename prefix
        if (!is_null($cachePrefix)) {
            $this->prefix = $cachePrefix;
        }

        // config options are passed to the cache as an array
        $this->options = [
            'cacheDir' => $this->dir,
            'fileNamePrefix' => $this->prefix,
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
            $this->enable = $enable;
            return null;
        } else {
            return $this->enable;
        }
    }

    /**
     * Get/Set the flag to debug a cache object (prints out miss/hit state with execution time).
     *
     * @param bool $debug True to activate debugging or false.
     * @return ?bool Debug flag or null
     */
    public function debug(?bool $debug = null): ?bool
    {
        if (is_bool($debug)) {
            $this->debug = $debug;
            return null;
        } else {
            return $this->debug;
        }
    }

    /**
     * Get/Set flag to print out cache info as HTML comment.
     *
     * @param ?bool $htmlcomment True debugging or false.
     * @return ?bool Htmlcomment flag or null
     */
    public function htmlComment(?bool $htmlcomment): ?bool
    {
        if (is_bool($htmlcomment)) {
            $this->htmlComment = $htmlcomment;
            return null;
        } else {
            return $this->htmlComment;
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
            $this->lifetime = $seconds;
            return null;
        } else {
            return $this->lifetime;
        }
    }

    /**
     * Get/Set template to use on printing the cache info.
     *
     * @param string $template Template string including the '%s' format definition.
     */
    public function infoTemplate(string $template)
    {
        $this->debugTpl = $template;
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
        $this->idOptions[$name] = $value;
    }

    /**
     * Returns information cache hit/miss and execution time if caching is enabled.
     *
     * @return ?string Information about cache if caching is enabled, otherwise null.
     */
    public function getInfo(): ?string
    {
        if ($this->enable) {
            return $this->debugMsg;
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
        $id = $this->id;
        $group = $this->group;

        // this is already cached return it from the cache so that the
        // user can use the cache content and stop script execution
        if ($content = $this->fileCache->get($id, $group)) {
            return $content;
        }

        // WARNING: we need the output buffer, possible clashes
        ob_start();
        ob_implicit_flush(false);

        return '';
    }

    /**
     * Handles PEAR caching.
     *
     * The script will be terminated by calling `die()`, if any cached content is found.
     *
     * @param ?int $pageStartTime [optional] Optional start time, e.g. start time of the main script
     * @throws cInvalidArgumentException
     */
    public function start(?int $pageStartTime = null)
    {
        if (!$this->enable) {
            return;
        }

        $this->startTime = $this->getMicroTime();

        // set cache object and unique id
        $this->initFileCache();

        // check if it's cached and start the output buffering if necessary
        if ($content = $this->_start()) {
            // raise beforeoutput event
            $this->raiseEvent('beforeoutput');

            $fEndTime = $this->getMicroTime();
            if ($this->htmlComment) {
                $time = sprintf("%2.4f", $fEndTime - $this->startTime);
                $exp = ($this->lifetime == 0 ? 'infinite' : date('Y-m-d H:i:s', time() + $this->lifetime));
                $content .= sprintf($this->htmlCommentTpl, 'HIT', $time . ' sec.', $exp);
                if ($pageStartTime != null) {
                    $content .= '<!-- [' . sprintf("%2.4f", $fEndTime - $pageStartTime) . '] -->';
                }
            }

            if ($this->debug) {
                $info = sprintf("HIT: %2.4f sec.", $fEndTime - $this->startTime);
                $info = sprintf($this->debugTpl, $info);
                $content = str_ireplace('</body>', $info . "\n</body>", $content);
            }

            echo $content;

            // raise afteroutput event
            $this->raiseEvent('afteroutput');

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
        if (!$this->enable) {
            return;
        }

        $content = ob_get_contents();
        ob_end_clean();

        $this->fileCache->save($content, $this->id, $this->group);

        echo $content;

        if ($this->debug) {
            $this->debugMsg .= "\n" . sprintf("MISS: %2.4f sec.\n", $this->getMicroTime() - $this->startTime);
            $this->debugMsg = sprintf($this->debugTpl, $this->debugMsg);
        }
    }

    /**
     * Removes any cached content if exists.
     *
     * This is necessary to delete cached articles if they are changed at the backend.
     *
     * @throws cInvalidArgumentException
     */
    public function removeFromCache()
    {
        // set cache object and unique id
        $this->initFileCache();
        $this->fileCache->remove($this->id, $this->group);
    }

    /**
     * Creates one-time an instance of a PEAR cache output object and also
     * the unique id, if proper $this->_oPearCache is not set.
     */
    protected function initFileCache()
    {
        if (is_object($this->fileCache)) {
            return;
        }

        // create an output cache object mode - file storage
        $this->fileCache = new cFileCache($this->options);

        // generate an ID from whatever might influence the script behaviour
        $this->id = $this->fileCache->generateID($this->idOptions);
    }

    /**
     * Raises any defined event code by using eval().
     *
     * @param string $name Name of event to raise
     */
    protected function raiseEvent(string $name)
    {
        // skip if event does not exist
        if (!isset($this->eventCode[$name]) && !is_array($this->eventCode[$name])) {
            return;
        }

        // loop array and execute each defined php-code
        foreach ($this->eventCode[$name] as $code) {
            eval($code);
        }
    }

    /**
     * Returns microtime (UNIX timestamp), used to calculate the time of execution.
     *
     * @return float Timestamp
     */
    protected function getMicroTime(): float
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
     *        don't cache output if we have a CONTENIDO variable,
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
            $this->enable = false;
            return;
        }

        // Set enable state of caching
        if (is_bool($aConf['enable'])) {
            $this->enable = $aConf['enable'];
        }
        if (!$this->enable) {
            return;
        }

        // Check if current article shouldn't be cached (by stese)
        $sExcludeIdarts = getEffectiveSetting('cache', 'excludeidarts', false);
        if ($sExcludeIdarts && cString::getStringLength($sExcludeIdarts) > 0) {
            $sExcludeIdarts = preg_replace("/[^0-9,]/", '', $sExcludeIdarts);
            $aExcludeIdart = explode(',', $sExcludeIdarts);
            if (in_array(cRegistry::getArticleId(), $aExcludeIdart)) {
                $this->enable = false;
                return;
            }
        }

        $this->db = $db;

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
            $this->eventCode = $aConf['raiseonevent'];
        }

        // Check, if code is to create
        $this->enable = !$this->isCodeToCreate($createCode);
        if (!$this->enable) {
            $this->removeFromCache();
        }
    }

    /**
     * Checks if the creation code flag is set.
     * Output will be loaded from the cache if no code is to be created.
     * It also checks the state of global variable $force.
     *
     * @param mixed $createCode State of create code (0 or 1).
     *      The state will be loaded from a database if the value is null
     * @return bool True if code is to create, otherwise false.
     * @throws cDbException|cException
     */
    protected function isCodeToCreate($createCode): bool
    {
        if (!$this->enable) {
            return false;
        }

        // check the content of global variable $force, get out if it's set to '1'
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
