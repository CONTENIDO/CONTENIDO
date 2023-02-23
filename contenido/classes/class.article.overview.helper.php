<?php

/**
 * This file contains the article overview helper class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Murat Purç <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the article content helper class
 * in CONTENIDO. The main task of this class is to optimize
 * computationally intensive function calls that are repeated at the
 * article list overview page. Therefore, this class uses heavily the
 * lazy loading approach, whenever it is possible.
 * Most of the values, returned by the functions, will be loaded/initialized
 * only during the first function call.
 *
 * NOTE:
 * This class is for internal usage in CONTENIDO core, therefore it is not meant
 * for public usage. Its interface and functionality may change in the future.
 *
 * TODO Has some similarities with {@see cBackendSearchHelper}, merge them together!
 *
 * @since CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Backend
 */
class cArticleOverviewHelper
{

    /**
     * @var cDb
     */
    protected $_db = null;

    /**
     * @var cAuth
     */
    protected $_auth = null;

    /**
     * @var cPermission
     */
    protected $_perm = null;

    /**
     * @var array
     */
    protected $_articles = null;

    /**
     * @var int
     */
    protected $_languageId = null;

    /**
     * @var int
     */
    protected $_clientId = null;

    /**
     * @var int
     */
    protected $_categoryId = null;

    /**
     * @var string
     */
    protected $_databaseTime;

    /**
     * @var string
     */
    protected $_textDirection;

    /**
     * @var array
     */
    protected $_articleMarks;

    /**
     * @var array
     */
    protected $_articleTemplateInfos;

    /**
     * @var array
     */
    protected $_categoryTemplateInfos;

    /**
     * @var array
     */
    protected $_articleInMultipleUse;

    /**
     * @var string
     */
    protected $_categoryBreadcrumb;

    /**
     * @var bool
     */
    protected $_hasArticleContentSyncPermission;

    /**
     * @var bool
     */
    protected $_hasArticleEditContentPermission;

    /**
     * @var bool
     */
    protected $_hasArticleEditPermission;

    /**
     * @var bool
     */
    protected $_hasArticleLockPermission;

    /**
     * @var bool
     */
    protected $_hasArticleMakeStartPermission;

    /**
     * @var bool
     */
    protected $_hasArticleDuplicatePermission;

    /**
     * @var bool
     */
    protected $_hasArticleMakeOnlinePermission;

    /**
     * @var bool
     */
    protected $_hasArticleDeletePermission;

    /**
     * Constructor.
     *
     * @param cDb $db Database instance
     * @param cAuth $auth Auth instance
     * @param cPermission $perm Permission instance
     * @param array $articles List of articles
     * @param int $idcat Category id
     * @param int $lang Language id
     * @param int $client Client id
     */
    public function __construct(
        cDb $db, cAuth $auth, cPermission $perm, array $articles, int $idcat, int $lang, int $client
    )
    {
        $this->_db = $db;
        $this->_auth = $auth;
        $this->_perm = $perm;
        $this->_articles = $articles;
        $this->_categoryId = $idcat;
        $this->_languageId = $lang;
        $this->_clientId = $client;
    }

    /**
     * Articles list setter.
     *
     * @param array $articles
     * @return void
     */
    public function setArticles(array $articles)
    {
        $this->_articles = $articles;
    }

    /**
     * Returns current time from database.
     *
     * @return string
     * @throws cDbException|cInvalidArgumentException
     */
    public function getDatabaseTime(): string
    {
        if (!isset($this->_databaseTime)) {
            $this->_db->query('SELECT NOW() AS TIME');
            $this->_db->nextRecord();
            $this->_databaseTime = cSecurity::toString($this->_db->f('TIME'));
        }

        return $this->_databaseTime;
    }

    /**
     * Returns text direction for the current language.
     *
     * @return string
     * @throws cDbException|cException
     */
    public function getTextDirection(): string
    {
        if (!isset($this->_textDirection)) {
            cInclude('includes', 'functions.lang.php');
            $this->_textDirection = langGetTextDirection($this->_languageId);
        }

        return $this->_textDirection;
    }

    /**
     * Checks if the article is in use by another user.
     *
     * @param int $idartlang Article language id
     * @return bool
     * @throws cDbException|cException
     */
    public function isArticleInUse(int $idartlang): bool
    {
        if (!isset($this->_articleMarks)) {
            $this->_articleMarks = [];

            $ids = array_map(function($item) {
                return $item['idartlang'];
            }, $this->_articles);

            if (count($ids)) {
                $inUseColl = new cApiInUseCollection();
                $where = "`type` = 'article' AND `objectid` IN('" . implode("','", $ids) . "')";
                $inUseColl->select($where);
                while (($item = $inUseColl->next()) !== false) {
                    $this->_articleMarks[cSecurity::toInteger($item->get('objectid'))] = $item->get('userid');
                }
            }
        }

        if (isset($this->_articleMarks[$idartlang])) {
            return $this->_articleMarks[$idartlang] !== $this->_auth->auth['uid'];
        }

        return false;
    }

    /**
     * Checks if the article is used in multiple categories.
     *
     * @param int $idart Article id
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function isArticleInMultipleUse(int $idart): bool
    {
        if (!isset($this->_articleInMultipleUse)) {
            $this->_articleInMultipleUse = [];
            $sql = "SELECT `idart`, COUNT(*) AS `count` FROM `%s` GROUP BY `idart` HAVING `count` > 1";
            $this->_db->query($sql, cRegistry::getDbTableName('cat_art'));
            while ($this->_db->nextRecord()) {
                $_idart = cSecurity::toInteger($this->_db->f('idart'));
                $this->_articleInMultipleUse[$_idart] = cSecurity::toInteger($this->_db->f('count'));
            }
        }

        return isset($this->_articleInMultipleUse[$idart]);
    }

    /**
     * Returns the user of an article, which is marked as "in use".
     *
     * @param int $idartlang Article language id
     * @return cApiUser|null
     * @throws cDbException|cException
     */
    public function getArticleInUseUser(int $idartlang)
    {
        if ($this->isArticleInUse($idartlang)) {
            $userid = $this->_articleMarks[$idartlang];
            return new cApiUser($userid);
        }

        return null;
    }

    /**
     * Returns the article template info array.
     *
     * @param int $idartlang Article language id
     * @return array|mixed
     * @throws cDbException|cInvalidArgumentException
     */
    public function getArticleTemplateInfo(int $idartlang)
    {
        $idartlang = cSecurity::toInteger($idartlang);

        if (!isset($this->_articleTemplateInfos)) {
            $this->_articleTemplateInfos = [];

            $ids = array_filter(array_map(function($item) {
                return $item['idtplcfg'];
            }, $this->_articles));

            if (count($ids)) {
                $sql = "-- cArticleOverviewHelper->getArticleTemplate()
                    SELECT
                        a.idtplcfg AS idtplcfg,
                        b.name AS name,
                        b.idtpl AS idtpl,
                        b.description AS description
                     FROM
                        " . cRegistry::getDbTableName('tpl_conf') . " AS a,
                        " . cRegistry::getDbTableName('tpl') . " AS b
                     WHERE
                        a.idtplcfg IN (" . implode(',', $ids) . ") AND
                        a.idtpl = b.idtpl";

                $this->_db->query($sql);
                while ($this->_db->nextRecord()) {
                    $idtplcfg = cSecurity::toInteger($this->_db->f('idtplcfg'));
                    $this->_articleTemplateInfos[$idtplcfg] = [
                        'idtplcfg' => $idtplcfg,
                        'idtpl' => cSecurity::toInteger($this->_db->f('idtpl')),
                        'name' => $this->_db->f('name'),
                        'description' => $this->_db->f('description') ?? '',
                    ];
                }
            }
        }

        $tplInfo = [];
        foreach ($this->_articles as $article) {
            if ($article['idartlang'] === $idartlang) {
                $tplInfo = $this->_articleTemplateInfos[$article['idtplcfg']] ?? [];
                break;
            }
        }

        return $tplInfo;
    }

    /**
     * Returns the article template info array.
     * Will be used, if the article has not its own template configuration.
     *
     * @return array
     * @throws cDbException|cInvalidArgumentException
     */
    public function getCategoryTemplateInfos(): array
    {
        if (!isset($this->_categoryTemplateInfos)) {
            $this->_categoryTemplateInfos = [];

            $sql = "-- cArticleOverviewHelper->_getCategoryTemplateInfos()
                SELECT
                    c.idtpl       AS idtpl,
                    c.name        AS name,
                    c.description AS description,
                    b.idtplcfg    AS idtplcfg
                FROM
                    " . cRegistry::getDbTableName('tpl_conf') . " AS a,
                    " . cRegistry::getDbTableName('cat_lang') . " AS b,
                    " . cRegistry::getDbTableName('tpl') . "      AS c
                WHERE
                    b.idcat    = " . $this->_categoryId . " AND
                    b.idlang   = " . $this->_languageId . " AND
                    b.idtplcfg = a.idtplcfg AND
                    c.idtpl    = a.idtpl AND
                    c.idclient = " . $this->_clientId;
            $this->_db->query($sql);
            if ($this->_db->nextRecord()) {
                $idtplcfg = cSecurity::toInteger($this->_db->f('idtplcfg'));
                $this->_categoryTemplateInfos = [
                    'idtplcfg' => $idtplcfg,
                    'idtpl' => cSecurity::toInteger($this->_db->f('idtpl')),
                    'name' => $this->_db->f('name'),
                    'description' => $this->_db->f('description') ?? '',
                ];
            }
        }

        return $this->_categoryTemplateInfos;
    }

    /**
     * Returns the category breadcrumb (category path).
     *
     * @return string
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getCategoryBreadcrumb(): string
    {
        if (!isset($this->_categoryBreadcrumb)) {
            $this->_categoryBreadcrumb = '';
            conCreateLocationString($this->_categoryId, '&nbsp;/&nbsp;', $this->_categoryBreadcrumb);
        }

        return $this->_categoryBreadcrumb;
    }

    /**
     * Checks if the user has permission to sync article content.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleContentSyncPermission(): bool
    {
        if (!isset($this->_hasArticleContentSyncPermission)) {
            $this->_hasArticleContentSyncPermission = $this->_checkPermission(
                'con', 'con_syncarticle', $this->_categoryId
            );
        }

        return $this->_hasArticleContentSyncPermission;
    }

    /**
     * Checks if the user has permission to edit article content.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleEditContentPermission(): bool
    {
        if (!isset($this->_hasArticleEditContentPermission)) {
            $this->_hasArticleEditContentPermission = $this->_checkPermission(
                'con_editcontent', 'con_editart', $this->_categoryId
            );
        }

        return $this->_hasArticleEditContentPermission;
    }

    /**
     * Checks if the user has permission to edit article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleEditPermission(): bool
    {
        if (!isset($this->_hasArticleEditPermission)) {
            $this->_hasArticleEditPermission = $this->_checkPermission(
                'con_editart', 'con_edit', $this->_categoryId
            );
        }

        return $this->_hasArticleEditPermission;
    }

    /**
     * Checks if the user has permission to lock article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleLockPermission(): bool
    {
        if (!isset($this->_hasArticleLockPermission)) {
            $this->_hasArticleLockPermission = $this->_checkPermission(
                'con', 'con_lock', $this->_categoryId
            );
        }

        return $this->_hasArticleLockPermission;
    }

    /**
     * Checks if the user has permission to make an article a start article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleMakeStartPermission(): bool
    {
        if (!isset($this->_hasArticleMakeStartPermission)) {
            $this->_hasArticleMakeStartPermission = $this->_checkPermission(
                'con', 'con_makestart', $this->_categoryId
            );
        }

        return $this->_hasArticleMakeStartPermission;
    }

    /**
     * Checks if the user has permission to duplicate article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleDuplicatePermission(): bool
    {
        if (!isset($this->_hasArticleDuplicatePermission)) {
            $this->_hasArticleDuplicatePermission = $this->_checkPermission(
                'con', 'con_duplicate', $this->_categoryId
            );
        }

        return $this->_hasArticleDuplicatePermission;
    }

    /**
     * Checks if the user has permission to make an article online/offline.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleMakeOnlinePermission(): bool
    {
        if (!isset($this->_hasArticleMakeOnlinePermission)) {
            $this->_hasArticleMakeOnlinePermission = $this->_checkPermission(
                'con', 'con_makeonline', $this->_categoryId
            );
        }

        return $this->_hasArticleMakeOnlinePermission;
    }

    /**
     * Checks if the user has permission to delete article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleDeletePermission(): bool
    {
        if (!isset($this->_hasArticleDeletePermission)) {
            $this->_hasArticleDeletePermission = $this->_checkPermission(
                'con', 'con_deleteart', $this->_categoryId
            );
        }

        return $this->_hasArticleDeletePermission;
    }

    /**
     * Checks the permission for an area, the action and the item.
     *
     * @param $area
     * @param $action
     * @param $item
     * @return bool
     * @throws cDbException|cException
     */
    protected function _checkPermission($area, $action, $item): bool
    {
        return $this->_perm->have_perm_area_action($area, $action)
            || $this->_perm->have_perm_area_action_item($area, $action, $item);
    }

}