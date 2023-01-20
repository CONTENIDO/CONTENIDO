<?php
/**
 * AMR url stack class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Mod rewrite url stack class. Provides features to collect urls and to get the
 * pretty path and names of categories/articles at one go.
 *
 * Main goal of this class is to collect urls and to get the urlpath and urlname
 * of the related categories/articles at one go. This will reduce the queries
 * against the database.
 * Therefore, the full advantage will be taken by rewriting the urls at codeoutput
 * in front_content.php, where you will be able to collect all urls at once...
 *
 * Usage:
 * <code>
 * // get the instance
 * $oMRUrlStack = ModRewriteUrlStack::getInstance();
 *
 * // add several urls to fill the stack
 * $oMRUrlStack->add('front_content.php?idcat=123');
 * $oMRUrlStack->add('front_content.php?idart=321');
 * $oMRUrlStack->add('front_content.php?idcatart=213');
 * $oMRUrlStack->add('front_content.php?idcatlang=213');
 * $oMRUrlStack->add('front_content.php?idartlang=312');
 *
 * // now the first call will get the pretty path and names from database at one go
 * $aPrettyParts = $oMRUrlStack->getPrettyUrlParts('front_content.php?idcat=123');
 * echo $aPrettyParts['urlpath']; // something like 'Main-category-name/Category-name/Another-category-name/'
 * echo $aPrettyParts['urlname']; // something like 'Name-of-an-article'
 * </code>
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
class ModRewriteUrlStack {

    /**
     * Self instance
     *
     * @var  ModRewriteUrlStack
     */
    private static $_instance;

    /**
     * Database object
     *
     * @var  cDb
     */
    private $_oDb;

    /**
     * Array for urls
     *
     * @var  array
     */
    private $_aUrls = [];

    /**
     * Url stack array
     *
     * @var  array
     */
    private $_aStack = [];

    /**
     * CONTENIDO related parameter array
     *
     * @var  array
     */
    private $_aConParams = [
        'idcat' => 1, 'idart' => 1, 'lang' => 1, 'idcatlang' => 1, 'idcatart' => 1, 'idartlang' => 1
    ];

    /**
     * Database tables array
     *
     * @var  array
     */
    private $_aTab;

    /**
     * Language id
     *
     * @var  int
     */
    private $_idLang;

    /**
     * Constructor, sets some properties.
     */
    private function __construct() {
        $cfg = cRegistry::getConfig();
        $this->_oDb = cRegistry::getDb();
        $this->_aTab = $cfg['tab'];
        $this->_idLang = cRegistry::getLanguageId();
    }

    /**
     * Returns an instance of ModRewriteUrlStack (singleton implementation)
     *
     * @return  ModRewriteUrlStack
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new ModRewriteUrlStack();
        }
        return self::$_instance;
    }

    /**
     * Adds an url to the stack
     *
     * @param  string $url Url, like front_content.php?idcat=123...
     */
    public function add($url) {
        $url = ModRewrite::urlPreClean($url);
        if (isset($this->_aUrls[$url])) {
            return;
        }

        $aUrl = $this->_extractUrl($url);

        // cleanup parameter
        foreach ($aUrl['params'] as $p => $v) {
            if (!isset($this->_aConParams[$p])) {
                unset($aUrl['params'][$p]);
            } else {
                $aUrl['params'][$p] = (int) $v;
            }
        }

        // add language id, if not available
        if ((int) mr_arrayValue($aUrl['params'], 'lang') == 0) {
            $aUrl['params']['lang'] = $this->_idLang;
        }

        $sStackId = $this->_makeStackId($aUrl['params']);
        $this->_aUrls[$url] = $sStackId;
        $this->_aStack[$sStackId] = ['params' => $aUrl['params']];
    }

    /**
     * Returns the pretty url-parts (only category path an article name) of the
     * desired url.
     *
     * @param   string  $url  Url, like front_content.php?idcat=123...
     *
     * @return  array   Associative array like
     * <code>
     * $arr['urlpath']
     * $arr['urlname']
     * </code>
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function getPrettyUrlParts($url) {
        $url = ModRewrite::urlPreClean($url);
        if (!isset($this->_aUrls[$url])) {
            $this->add($url);
        }

        $sStackId = $this->_aUrls[$url];
        if (!isset($this->_aStack[$sStackId]['urlpath'])) {
            $this->_chunkSetPrettyUrlParts($sStackId);
        }
        return [
            'urlpath' => $this->_aStack[$sStackId]['urlpath'],
            'urlname' => $this->_aStack[$sStackId]['urlname']
        ];
    }

    /**
     * Extracts passed url using parse_url and adds also the 'params' array to it
     *
     * @param   string  $url  Url, like front_content.php?idcat=123...
     * @return  array  Components containing result of parse_url with additional
     *                 'params' array
     */
    private function _extractUrl($url) {
        return cUri::getInstance()->parse($url);
    }

    /**
     * Extracts article or category related parameter from passed params array
     * and generates an identifier.
     *
     * @param   array   $aParams  Parameter array
     * @return  string  Composed stack id
     */
    private function _makeStackId(array $aParams) {
        // idcatart
        if ((int) mr_arrayValue($aParams, 'idart') > 0) {
            $sStackId = 'idart_' . $aParams['idart'] . '_lang_' . $aParams['lang'];
        } elseif ((int) mr_arrayValue($aParams, 'idartlang') > 0) {
            $sStackId = 'idartlang_' . $aParams['idartlang'];
        } elseif ((int) mr_arrayValue($aParams, 'idcatart') > 0) {
            $sStackId = 'idcatart_' . $aParams['idcatart'] . '_lang_' . $aParams['lang'];
        } elseif ((int) mr_arrayValue($aParams, 'idcat') > 0) {
            $sStackId = 'idcat_' . $aParams['idcat'] . '_lang_' . $aParams['lang'];
        } elseif ((int) mr_arrayValue($aParams, 'idcatlang') > 0) {
            $sStackId = 'idcatlang_' . $aParams['idcatlang'];
        } else {
            $sStackId = 'lang_' . $aParams['lang'];
        }
        return $sStackId;
    }

    /**
     * Main function to get the url-parts of urls.
     *
     * Composes the query by looping through stored but non-processed urls, executes
     * the query and adds the (urlpath and urlname) result to the stack.
     *
     * @param $sStackId
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    private function _chunkSetPrettyUrlParts($sStackId) {
        // collect stack parameter to get urlpath and urlname
        $aStack = [];
        foreach ($this->_aStack as $stackId => $item) {
            if (!isset($item['urlpath'])) {
                // pretty url is to create
                $aStack[$stackId] = $item;
            }
        }

        // now, it's time to compose the where clause of the query
        $sWhere = '';
        foreach ($aStack as $stackId => $item) {
            if ($stackId == $sStackId) {
                $aP = $item['params'];
                if ((int)mr_arrayValue($aP, 'idart') > 0) {
                    $sWhere .= '(al.idart = ' . $aP['idart'] . ' AND al.idlang = ' . $aP['lang'] . ') OR ';
                } elseif ((int)mr_arrayValue($aP, 'idartlang') > 0) {
                    $sWhere .= '(al.idartlang = ' . $aP['idartlang'] . ') OR ';
                } elseif ((int)mr_arrayValue($aP, 'idcat') > 0) {
                    $sWhere .= '(cl.idcat = ' . $aP['idcat'] . ' AND cl.idlang = ' . $aP['lang'] . ' AND cl.startidartlang = al.idartlang) OR ';
                } elseif ((int)mr_arrayValue($aP, 'idcatart') > 0) {
                    $sWhere .= '(ca.idcatart = ' . $aP['idcatart'] . ' AND ca.idart = al.idart AND al.idlang = ' . $aP['lang'] . ') OR ';
                } elseif ((int)mr_arrayValue($aP, 'idcatlang') > 0) {
                    $sWhere .= '(cl.idcatlang = ' . $aP['idcatlang'] . ' AND cl.startidartlang = al.idartlang) OR ';
                }
            }
        }
        if ($sWhere == '') {
            return;
        }
        $sWhere = cString::getPartOfString($sWhere, 0, -4);
        $sWhere = str_replace(' OR ', " OR \n", $sWhere);

        // compose query and execute it
        $sql = <<<SQL
SELECT
        al.idartlang, al.idart, al.idlang as lang, al.urlname, cl.idcatlang, cl.idcat,
        cl.urlpath, ca.idcatart
FROM
        {$this->_aTab['art_lang']} AS al, {$this->_aTab['cat_lang']} AS cl, {$this->_aTab['cat_art']} AS ca
WHERE
        al.idart = ca.idart AND
        ca.idcat = cl.idcat AND
        al.idlang = cl.idlang AND
        ($sWhere)
SQL;
        ModRewriteDebugger::add($sql, 'ModRewriteUrlStack->_chunkSetPrettyUrlParts() $sql');

        $aNewStack = [];

        // create array of fields, which are to reduce step by step from record set below
        $aFields = ['', 'idart', 'idartlang', 'idcatart', 'idcat'];

        $this->_oDb->query($sql);
        while ($this->_oDb->nextRecord()) {
            $aRS = $this->_oDb->getRecord();

            // loop thru fields array
            foreach ($aFields as $field) {
                if (isset($aRS[$field])) {
                    // reduce existing field
                    unset($aRS[$field]);
                }
                $rsStackID = $this->_makeStackId($aRS);
                if (isset($aStack[$rsStackID])) {
                    // matching stack entry found, add urlpath and urlname to the new stack
                    $aNewStack[$rsStackID]['urlpath'] = $aRS['urlpath'];
                    $aNewStack[$rsStackID]['urlname'] = $aRS['urlname'];
                    break;
                }
            }
        }
        ModRewriteDebugger::add($aNewStack, 'ModRewriteUrlStack->_chunkSetPrettyUrlParts() $aNewStack');

        // merge stack data
        $this->_aStack = array_merge($this->_aStack, $aNewStack);
    }

}
