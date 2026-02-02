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
 * The main goal of this class is to collect urls and to get the urlpath and urlname
 * of the related categories/articles at one go. This will reduce the queries
 * against the database.
 * Therefore, the full advantage will be taken by rewriting the urls at codeoutput
 * in front_content.php, where you will be able to collect all urls at once...
 *
 * Usage:
 * <code>
 * // get the instance
 * $oMRUrlStack = PiModRewriteUrlStackService::getInstance();
 *
 * // add several urls to fill the stack
 * $oMRUrlStack->add('front_content.php?idcat=123');
 * $oMRUrlStack->add('front_content.php?idart=321');
 * $oMRUrlStack->add('front_content.php?idcatart=213');
 * $oMRUrlStack->add('front_content.php?idcatlang=213');
 * $oMRUrlStack->add('front_content.php?idartlang=312');
 *
 * // now the first call will get the pretty path and names from the database at one go
 * $urlPathsDto = $oMRUrlStack->getPrettyUrlDto('front_content.php?idcat=123');
 * echo $urlPathsDto->getUrlPath(); // something like 'Main-category-name/Category-name/Another-category-name/'
 * echo $urlPathsDto->getUrlName(); // something like 'Name-of-an-article'
 * </code>
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteUrlStackService
{

    /**
     * @var PiModRewriteUrlStackService Self-instance
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
     * @var int Language id
     */
    private $languageId;

    /**
     * Constructor, sets some properties.
     */
    private function __construct()
    {
        $this->db = cRegistry::getDb();
        $this->languageId = cRegistry::getLanguageId();
    }

    /**
     * Returns an instance of PiModRewriteUrlStackService (singleton implementation)
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Adds the url to the stack.
     *
     * @param string $url Url, like front_content.php?idcat=123...
     */
    public function add(string $url)
    {
        $url = PiModRewrite::urlPreClean($url);
        if (isset($this->urls[$url])) {
            return;
        }

        $urlComponents = $this->extractUrl($url);

        // cleanup parameter
        foreach ($urlComponents['params'] as $p => $v) {
            if (!isset($this->conParams[$p])) {
                unset($urlComponents['params'][$p]);
            } else {
                $urlComponents['params'][$p] = cSecurity::toInteger($v);
            }
        }

        // add language id, if not available
        if (cSecurity::toInteger(PiModRewriteUtil::arrayValue($urlComponents['params'], 'lang')) == 0) {
            $urlComponents['params']['lang'] = $this->languageId;
        }

        $stackId = $this->makeStackId($urlComponents['params']);
        $this->urls[$url] = $stackId;
        $this->urlStack[$stackId] = ['params' => $urlComponents['params']];
    }

    /**
     * Returns the pretty url-parts (only category path an article name) of the desired url.
     *
     * @param string $url Url, like front_content.php?idcat=123...
     * @return array{urlpath: string, urlname: string} Associative pretty url array
     * @throws cDbException|cInvalidArgumentException
     */
    public function getPrettyUrlParts(string $url): array
    {
        $dto = $this->getPrettyUrlDto($url);
        return [
            'urlpath' => $dto->getUrlPath(),
            'urlname' => $dto->getUrlName(),
        ];
    }

    /**
     * Returns the pretty url-parts DTO (only category path an article name) of the desired url.
     *
     * @param string $url Url, like front_content.php?idcat=123...
     * @throws cDbException|cInvalidArgumentException
     */
    public function getPrettyUrlDto(string $url): PiModRewritePrettyUrlDto
    {
        $url = PiModRewrite::urlPreClean($url);
        if (!isset($this->urls[$url])) {
            $this->add($url);
        }

        $stackId = $this->urls[$url];
        if (!isset($this->urlStack[$stackId]['urlpath'])) {
            $this->chunkSetPrettyUrlParts($stackId);
        }

        return new PiModRewritePrettyUrlDto(
            $this->urlStack[$stackId]['urlpath'] ?? '',
            $this->urlStack[$stackId]['urlname'] ?? ''
        );
    }

    /**
     * Extracts the passed url using parse_url and adds the 'params' array to it.
     *
     * @param string $url Url, like front_content.php?idcat=123...
     * @return array Components containing the result of parse_url with additional 'params' array
     */
    private function extractUrl(string $url): array
    {
        return cUri::getInstance()->parse($url);
    }

    /**
     * Extracts article or category related parameter from the assigned params array
     * and generates an identifier.
     *
     * @param array $params Parameter array
     * @return string Composed stack id
     */
    private function makeStackId(array $params): string
    {
        // idcatart
        if (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idart')) > 0) {
            $stackId = 'idart_' . $params['idart'] . '_lang_' . $params['lang'];
        } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idartlang')) > 0) {
            $stackId = 'idartlang_' . $params['idartlang'];
        } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idcatart')) > 0) {
            $stackId = 'idcatart_' . $params['idcatart'] . '_lang_' . $params['lang'];
        } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idcat')) > 0) {
            $stackId = 'idcat_' . $params['idcat'] . '_lang_' . $params['lang'];
        } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idcatlang')) > 0) {
            $stackId = 'idcatlang_' . $params['idcatlang'];
        } else {
            $stackId = 'lang_' . $params['lang'];
        }

        return $stackId;
    }

    /**
     * Main function to get the url-parts of urls.
     *
     * Composes the query by looping through stored but non-processed urls, executes
     * the query and adds the (urlpath and urlname) result to the stack.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    private function chunkSetPrettyUrlParts(string $stackId)
    {
        // collect stack parameter to get urlpath, and the urlname of pretty url is to create
        $stack = array_filter($this->urlStack, function ($item) {
            return !isset($item['urlpath']);
        });

        // now, it's time to compose the where clause of the query
        $where = '';
        foreach ($stack as $_stackId => $item) {
            if ($_stackId === $stackId) {
                $params = $item['params'];
                if (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idart')) > 0) {
                    $where .= sprintf('(al.idart = %d AND al.idlang = %d) OR ', $params['idart'], $params['lang']);
                } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idartlang')) > 0) {
                    $where .= sprintf('(al.idartlang = %d) OR ', $params['idartlang']);
                } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idcat')) > 0) {
                    $where .= sprintf(
                        '(cl.idcat = %d AND cl.idlang = %d AND cl.startidartlang = al.idartlang) OR ',
                        $params['idcat'],
                        $params['lang']
                    );
                } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idcatart')) > 0) {
                    $where .= sprintf(
                        '(ca.idcatart = %d AND ca.idart = al.idart AND al.idlang = %d) OR ',
                        $params['idcatart'],
                        $params['lang']
                    );
                } elseif (cSecurity::toInteger(PiModRewriteUtil::arrayValue($params, 'idcatlang')) > 0) {
                    $where .= sprintf(
                        '(cl.idcatlang = %d AND cl.startidartlang = al.idartlang) OR ',
                        $params['idcatlang']
                    );
                }
            }
        }
        if ($where == '') {
            return;
        }
        $where = cString::getPartOfString($where, 0, -4);
        $where = str_replace(' OR ', " OR \n", $where);

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
        ($where)
SQL;
        PiModRewriteDebugger::add($sql, __METHOD__ . ' $sql');

        $newStack = [];

        // create an array of fields, which are to reduce step by step from the record set below
        $fields = ['', 'idart', 'idartlang', 'idcatart', 'idcat'];

        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $records = $this->db->getRecord();
            foreach ($fields as $field) {
                if (isset($records[$field])) {
                    // reduce existing field
                    unset($records[$field]);
                }
                $rsStackID = $this->makeStackId($records);
                if (isset($stack[$rsStackID])) {
                    // matching stack entry found, add urlpath and urlname to the new stack
                    $newStack[$rsStackID]['urlpath'] = $records['urlpath'];
                    $newStack[$rsStackID]['urlname'] = $records['urlname'];
                    break;
                }
            }
        }
        PiModRewriteDebugger::add($newStack, __METHOD__ . ' $newStack');

        // merge stack data
        $this->urlStack = array_merge($this->urlStack, $newStack);
    }

}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUrlStackService} instead.
 */
class ModRewriteUrlStack extends PiModRewriteUrlStackService
{}
