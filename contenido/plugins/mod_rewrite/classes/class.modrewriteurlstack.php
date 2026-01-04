<?php

/**
 * AMR url stack class
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewriteUrlStack
{

    /**
     * @var ModRewriteUrlStack Self instance
     */
    private static $instance;

    /**
     * @var cDb Database object
     */
    private $db;

    /**
     * @var array Array for urls
     */
    private $urls = [];

    /**
     * @var array Url stack array
     */
    private $urlStack = [];

    /**
     * @var array CONTENIDO related parameter array
     */
    private $conParams = [
        'idcat' => 1,
        'idart' => 1,
        'lang' => 1,
        'idcatlang' => 1,
        'idcatart' => 1,
        'idartlang' => 1,
    ];

    /**
     * Language id
     *
     * @var int
     */
    private $_idLang;

    /**
     * Constructor, sets some properties.
     */
    private function __construct()
    {
        $this->db = cRegistry::getDb();
        $this->_idLang = cRegistry::getLanguageId();
    }

    /**
     * Returns an instance of ModRewriteUrlStack (singleton implementation)
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Adds an url to the stack
     *
     * @param string $url Url, like front_content.php?idcat=123...
     */
    public function add(string $url)
    {
        $url = ModRewrite::urlPreClean($url);
        if (isset($this->urls[$url])) {
            return;
        }

        $aUrl = $this->_extractUrl($url);

        // cleanup parameter
        foreach ($aUrl['params'] as $p => $v) {
            if (!isset($this->conParams[$p])) {
                unset($aUrl['params'][$p]);
            } else {
                $aUrl['params'][$p] = (int)$v;
            }
        }

        // add language id, if not available
        if ((int)mr_arrayValue($aUrl['params'], 'lang') == 0) {
            $aUrl['params']['lang'] = $this->_idLang;
        }

        $stackId = $this->_makeStackId($aUrl['params']);
        $this->urls[$url] = $stackId;
        $this->urlStack[$stackId] = ['params' => $aUrl['params']];
    }

    /**
     * Returns the pretty url-parts (only category path an article name) of the
     * desired url.
     *
     * @param string $url Url, like front_content.php?idcat=123...
     *
     * @return array  Associative array like
     * <code>
     * $arr['urlpath']
     * $arr['urlname']
     * </code>
     * @throws cDbException|cInvalidArgumentException
     */
    public function getPrettyUrlParts(string $url): array
    {
        $url = ModRewrite::urlPreClean($url);
        if (!isset($this->urls[$url])) {
            $this->add($url);
        }

        $stackId = $this->urls[$url];
        if (!isset($this->urlStack[$stackId]['urlpath'])) {
            $this->_chunkSetPrettyUrlParts($stackId);
        }
        return [
            'urlpath' => $this->urlStack[$stackId]['urlpath'] ?? '',
            'urlname' => $this->urlStack[$stackId]['urlname'] ?? ''
        ];
    }

    /**
     * Extracts passed url using parse_url and adds also the 'params' array to it
     *
     * @param string $url Url, like front_content.php?idcat=123...
     * @return array Components containing result of parse_url with additional 'params' array
     */
    private function _extractUrl(string $url): array
    {
        return cUri::getInstance()->parse($url);
    }

    /**
     * Extracts article or category related parameter from passed params array
     * and generates an identifier.
     *
     * @param array $aParams Parameter array
     * @return string Composed stack id
     */
    private function _makeStackId(array $aParams): string
    {
        // idcatart
        if ((int)mr_arrayValue($aParams, 'idart') > 0) {
            $stackId = 'idart_' . $aParams['idart'] . '_lang_' . $aParams['lang'];
        } elseif ((int)mr_arrayValue($aParams, 'idartlang') > 0) {
            $stackId = 'idartlang_' . $aParams['idartlang'];
        } elseif ((int)mr_arrayValue($aParams, 'idcatart') > 0) {
            $stackId = 'idcatart_' . $aParams['idcatart'] . '_lang_' . $aParams['lang'];
        } elseif ((int)mr_arrayValue($aParams, 'idcat') > 0) {
            $stackId = 'idcat_' . $aParams['idcat'] . '_lang_' . $aParams['lang'];
        } elseif ((int)mr_arrayValue($aParams, 'idcatlang') > 0) {
            $stackId = 'idcatlang_' . $aParams['idcatlang'];
        } else {
            $stackId = 'lang_' . $aParams['lang'];
        }
        return $stackId;
    }

    /**
     * Main function to get the url-parts of urls.
     *
     * Composes the query by looping through stored but non-processed urls, executes
     * the query and adds the (urlpath and urlname) result to the stack.
     *
     * @param $stackId
     * @throws cDbException|cInvalidArgumentException
     */
    private function _chunkSetPrettyUrlParts($stackId)
    {
        // collect stack parameter to get urlpath and urlname
        $stack = [];
        foreach ($this->urlStack as $_stackId => $item) {
            if (!isset($item['urlpath'])) {
                // pretty url is to create
                $stack[$_stackId] = $item;
            }
        }

        // now, it's time to compose the where clause of the query
        $sWhere = '';
        foreach ($stack as $_stackId => $item) {
            if ($_stackId === $stackId) {
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

        $tabArtLang = cDb::getTableName('art_lang');
        $tabCatLang = cDb::getTableName('cat_lang');
        $tabCatArt = cDb::getTableName('cat_art');

        // compose query and execute it
        $sql = <<<SQL
SELECT
        al.idartlang, al.idart, al.idlang as lang, al.urlname, cl.idcatlang, cl.idcat,
        cl.urlpath, ca.idcatart
FROM
        $tabArtLang AS al, $tabCatLang AS cl, $tabCatArt AS ca
WHERE
        al.idart = ca.idart AND
        ca.idcat = cl.idcat AND
        al.idlang = cl.idlang AND
        ($sWhere)
SQL;
        ModRewriteDebugger::add($sql, 'ModRewriteUrlStack->_chunkSetPrettyUrlParts() $sql');

        $newStack = [];

        // create array of fields, which are to reduce step by step from record set below
        $fields = ['', 'idart', 'idartlang', 'idcatart', 'idcat'];

        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $rs = $this->db->getRecord();

            // loop through fields array
            foreach ($fields as $field) {
                if (isset($rs[$field])) {
                    // reduce existing field
                    unset($rs[$field]);
                }
                $rsStackID = $this->_makeStackId($rs);
                if (isset($stack[$rsStackID])) {
                    // matching stack entry found, add urlpath and urlname to the new stack
                    $newStack[$rsStackID]['urlpath'] = $rs['urlpath'];
                    $newStack[$rsStackID]['urlname'] = $rs['urlname'];
                    break;
                }
            }
        }
        ModRewriteDebugger::add($newStack, 'ModRewriteUrlStack->_chunkSetPrettyUrlParts() $newStack');

        // merge stack data
        $this->urlStack = array_merge($this->urlStack, $newStack);
    }

}
