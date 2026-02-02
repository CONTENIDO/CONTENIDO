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
class PiModRewriteTestService
{

    /**
     * @var int Max items to process
     */
    protected $maxItems;

    /**
     * @var string Actual resolved url
     */
    protected $resolvedUrl = '';

    /**
     * @var bool Flag about a found routing status
     */
    protected $routingFound = false;

    /**
     * @param int $maxItems Max items (urls to articles/categories) to process
     */
    public function __construct(int $maxItems)
    {
        $this->maxItems = $maxItems;
    }

    /**
     * Returns resolved URL
     */
    public function getResolvedUrl(): string
    {
        return $this->resolvedUrl;
    }

    /**
     * Returns flags about found routing
     */
    public function isRoutingFound(): bool
    {
        return $this->routingFound;
    }

    /**
     * Fetches the full structure of the installation (categories and articles) and returns it.
     *
     * @param ?int $clientId Client id
     * @param ?int $languageId Language id
     * @return array Full structure as follows
     * <code>
     *   $arr[idcat] = Category dataset
     *   $arr[idcat]['articles'][idart] = Article dataset
     * </code>
     * @throws cDbException
     */
    public function fetchFullStructure(?int $clientId = null, ?int $languageId = null): array
    {
        $db = cRegistry::getDb();
        $db2 = cRegistry::getDb();

        $clientId = $clientId ?? cRegistry::getClientId();
        $languageId = $languageId ?? cRegistry::getLanguageId();

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

            $categoryId = $db->f('idcat');
            $structure[$categoryId] = $db->getRecord();
            $structure[$categoryId]['articles'] = [];

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
                $categoryId,
                $languageId,
                $clientId
            );

            while ($db2->nextRecord()) {
                $articleId = $db2->f('idart');
                $structure[$categoryId]['articles'][$articleId] = $db2->getRecord();
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
            if (PiModRewriteRequestUtil::getRequest('idart')) {
                $param[] = 'idart=' . $arr['idart'];
            }
            if (PiModRewriteRequestUtil::getRequest('idcat')) {
                $param[] = 'idcat=' . $arr['idcat'];
            }
            if (PiModRewriteRequestUtil::getRequest('idcatart')) {
                $param[] = 'idcatart=' . $arr['idcatart'];
            }
            if (PiModRewriteRequestUtil::getRequest('idartlang')) {
                $param[] = 'idartlang=' . $arr['idartlang'];
            }
        }
        $param[] = 'foo=bar';

        return 'front_content.php?' . implode('&amp;', $param);
    }

    /**
     * Resolves variables of a page (idcat, idart, idclient, idlang, etc.) by
     * processing passed url using PiModRewriteFrontContentService
     *
     * @param string $url Url to resolve
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function resolveUrl(string $url): PiModRewriteResolvedUrlDto
    {
        // Reset some globals. The URL resolving relies on the value of these globals,
        // any pre-set value could fail the resolving process.
        foreach (['idart', 'idcat'] as $k) {
            if (isset($GLOBALS[$k])) {
                unset($GLOBALS[$k]);
            }
        }

        $aReturn = [];

        // create a mod rewrite controller instance and execute processing
        $oMRController = new PiModRewriteFrontContentService($url);
        $oMRController->execute();

        if ($oMRController->isError()) {
            // En error occurred (idcat and or idart couldn't detected by controller)
            $this->resolvedUrl = '';
            $this->routingFound = false;

            return new PiModRewriteResolvedUrlDto($oMRController->getError());
        } else {
            // Resolving was successful
            $this->resolvedUrl = $oMRController->getResolvedUrl();
            $this->routingFound = $oMRController->isRoutingFound();

            return new PiModRewriteResolvedUrlDto(
                null,
                $oMRController->getClient() ? $oMRController->getClient() : null,
                $oMRController->getChangeClient() ? $oMRController->getChangeClient() : null,
                $oMRController->getLang() ? $oMRController->getLang() : null,
                $oMRController->getChangeLang() ? $oMRController->getChangeLang() : null,
                $oMRController->getIdArt() ? $oMRController->getIdArt() : null,
                $oMRController->getIdCat() ? $oMRController->getIdCat() : null,
                !empty($oMRController->getPath()) ? $oMRController->getPath() : null
            );
        }
    }

    /**
     * Creates a readable string from an assigned data array.
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
