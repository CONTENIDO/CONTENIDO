<?php

/**
 * AMR test class
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
 * Mod rewrite test class.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewriteTest
{

    /**
     * Max items to process
     * @var int
     */
    protected $maxItems;

    /**
     * Actual resolved url
     * @var string
     */
    protected $resolvedUrl;

    /**
     * Routing found flag
     * @var bool
     */
    protected $routingFound = false;

    /**
     * Constructor
     * @param int $maxItems Max items (urls to articles/categories) to process
     */
    public function __construct(int $maxItems)
    {
        $this->maxItems = $maxItems;
    }

    /**
     * Returns resolved URL
     *
     * @return string Resolved URL
     */
    public function getResolvedUrl()
    {
        return $this->resolvedUrl;
    }

    /**
     * Returns flags about found routing
     *
     * @return bool
     */
    public function getRoutingFoundState()
    {
        return $this->routingFound;
    }

    /**
     * Fetches full structure of the installation (categories and articles) and returns it back.
     *
     * @param int $clientId Client id
     * @param int $languageId Language id
     * @return array Full structure as follows
     * <code>
     *   $arr[idcat] = Category dataset
     *   $arr[idcat]['articles'][idart] = Article dataset
     * </code>
     * @throws cDbException
     */
    public function fetchFullStructure($clientId = NULL, $languageId = NULL): array
    {
        $db = cRegistry::getDb();
        $db2 = cRegistry::getDb();

        if (!$clientId || (int)$clientId == 0) {
            $clientId = cRegistry::getClientId();
        }
        if (!$languageId || (int)$languageId == 0) {
            $languageId = cRegistry::getLanguageId();
        }

        $structure = [];

        $sql = "SELECT
                    *
                FROM
                    `%s` AS a,
                    `%s` AS b,
                    `%s` AS c
                WHERE
                    a.idcat = b.idcat AND
                    c.idcat = a.idcat AND
                    c.idclient = %d AND
                    b.idlang = %d
                ORDER BY
                    a.idtree";

        $db->query(
            $sql,
            cDb::getTableName('cat_tree'),
            cDb::getTableName('cat_lang'),
            cDb::getTableName('cat'),
            $clientId,
            $languageId
        );

        $counter = 0;

        while ($db->nextRecord()) {
            if (++$counter == $this->maxItems) {
                break; // break this loop
            }

            $idcat = $db->f('idcat');
            $structure[$idcat] = $db->getRecord();
            $structure[$idcat]['articles'] = [];

            $sql2 = "SELECT
                         *
                     FROM
                         `%s` AS a,
                         `%s` AS b,
                         `%s` AS c
                     WHERE
                         a.idcat = %d AND
                         b.idart = a.idart AND
                         c.idart = a.idart AND
                         c.idlang = %d AND
                         b.idclient = %d
                     ORDER BY
                         c.title ASC";

            $db2->query(
                $sql2,
                cDb::getTableName('cat_art'),
                cDb::getTableName('art'),
                cDb::getTableName('art_lang'),
                $idcat,
                $languageId,
                $clientId
            );

            while ($db2->nextRecord()) {
                $idart = $db2->f('idart');
                $structure[$idcat]['articles'][$idart] = $db2->getRecord();
                if (++$counter == $this->maxItems) {
                    break 2; // break this and also superior loop
                }
            }
        }

        return $structure;
    }

    /**
     * Creates a URL using passed data.
     *
     * The result is used to generate seo urls...
     *
     * @param array $arr Associative array with some data as follows:
     *      <code>
     *      $arr['idcat']
     *      $arr['idart']
     *      $arr['idcatart']
     *      $arr['idartlang']
     *      </code>
     * @param string $type Either 'c' or 'a' (category or article). If set to
     *      'c' only the parameter idcat will be added to the URL
     */
    public function composeURL(array $arr, string $type): string
    {
        $type = $type == 'a' ? 'a' : 'c';

        $param = [];

        if ($type == 'c') {
            $param[] = 'idcat=' . $arr['idcat'];
        } else {
            if (mr_getRequest('idart')) {
                $param[] = 'idart=' . $arr['idart'];
            }
            if (mr_getRequest('idcat')) {
                $param[] = 'idcat=' . $arr['idcat'];
            }
            if (mr_getRequest('idcatart')) {
                $param[] = 'idcatart=' . $arr['idcatart'];
            }
            if (mr_getRequest('idartlang')) {
                $param[] = 'idartlang=' . $arr['idartlang'];
            }
        }
        $param[] = 'foo=bar';

        return 'front_content.php?' . implode('&amp;', $param);
    }

    /**
     * Resolves variables of a page (idcat, idart, idclient, idlang, etc.) by
     * processing passed url using ModRewriteController
     *
     * @param string $url Url to resolve
     * @return array Associative array with resolved data
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function resolveUrl(string $url): array
    {
        // some globals to reset
        $aGlobs = [
            'mr_preprocessedPageError', 'idart', 'idcat'
        ];
        foreach ($aGlobs as $k) {
            if (isset($GLOBALS[$k])) {
                unset($GLOBALS[$k]);
            }
        }

        $aReturn = [];

        // create a mod rewrite controller instance and execute processing
        $oMRController = new ModRewriteController($url);
        $oMRController->execute();

        if ($oMRController->errorOccured()) {
            // an error occurred (idcat and or idart couldn't caught by controller)
            $aReturn['mr_preprocessedPageError'] = 1;
            $aReturn['error'] = $oMRController->getError();

            $this->resolvedUrl = '';
            $this->routingFound = false;
        } else {
            // set some global variables
            $this->resolvedUrl = $oMRController->getResolvedUrl();
            $this->routingFound = $oMRController->getRoutingFoundState();

            if ($oMRController->getClient()) {
                $aReturn['client'] = $oMRController->getClient();
            }

            if ($oMRController->getChangeClient()) {
                $aReturn['changeclient'] = $oMRController->getChangeClient();
            }

            if ($oMRController->getLang()) {
                $aReturn['lang'] = $oMRController->getLang();
            }

            if ($oMRController->getChangeLang()) {
                $aReturn['changelang'] = $oMRController->getChangeLang();
            }

            if ($oMRController->getIdArt()) {
                $aReturn['idart'] = $oMRController->getIdArt();
            }

            if ($oMRController->getIdCat()) {
                $aReturn['idcat'] = $oMRController->getIdCat();
            }

            if ($oMRController->getPath()) {
                $aReturn['path'] = $oMRController->getPath();
            }
        }

        return $aReturn;
    }

    /**
     * Creates a readable string from passed resolved data array.
     *
     * @param array $data Associative array with resolved data
     * @return string Readable resolved data
     */
    public function getReadableResolvedData(array $data): string
    {
        // compose resolved string
        $ret = '';
        foreach ($data as $k => $v) {
            $ret .= $k . '=' . $v . '; ';
        }
        return cString::getPartOfString($ret, 0, cString::getStringLength($ret) - 2);
    }

}
