<?php

/**
 * This file contains the backend search helper class.
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
 * This class contains functions for the backend search helper class
 * in CONTENIDO. The main task of this class is to optimize
 * computationally intensive function calls that are repeated at the
 * backend search page. Therefore, this class uses heavily the
 * lazy loading approach, whenever it is possible.
 * Most of the values, returned by the functions, will be loaded/initialized
 * only during the first function call.
 *
 * NOTE:
 * This class is for internal usage in CONTENIDO core, therefore it is not meant
 * for public usage. Its interface and functionality may change in the future.
 *
 * TODO Has some similarities with {@see cArticleOverviewHelper}, merge them together!
 *
 * @since CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Backend
 */
class cBackendSearchHelper
{

    const COMMON_CON_AND_ARTICLE_RIGHTS = [
        ['con', 'con_makestart'],
        ['con', 'con_makeonline'],
        ['con', 'con_deleteart'],
        ['con', 'con_tplcfg_edit'],
        ['con', 'con_makecatonline'],
        ['con', 'con_changetemplate'],
        ['con_editcontent', 'con_editart'],
        ['con_editart', 'con_edit'],
        ['con_editart', 'con_newart'],
        ['con_editart', 'con_saveart'],
    ];

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
     * @var int
     */
    protected $_languageId = null;

    /**
     * @var int
     */
    protected $_clientId = null;

    /**
     * @var array
     */
    protected $_articleInfos;

    /**
     * @var int[]
     */
    protected $_categoryIds = null;

    /**
     * @var array
     */
    protected $_categoryTemplateInfos;

    /**
     * @var array
     */
    protected $_categoryBreadcrumb;

    /**
     * @var array
     */
    protected $_articlePermissions;

    /**
     * @var bool
     */
    protected $_hasCommonContentPermission;

    /**
     * @var bool[]
     */
    protected $_hasArticleEditContentPermission;

    /**
     * @var bool[]
     */
    protected $_hasArticleMakeStartPermission;

    /**
     * @var bool[]
     */
    protected $_hasArticleDuplicatePermission;

    /**
     * @var bool[]
     */
    protected $_hasArticleDeletePermission;

    /**
     * Constructor.
     *
     * @param cDb $db Database instance
     * @param cAuth $auth Auth instance
     * @param cPermission $perm Permission instance
     * @param int $lang Language id
     * @param int $client Client id
     */
    public function __construct(cDb $db, cAuth $auth, cPermission $perm, int $lang, int $client)
    {
        $this->_db = $db;
        $this->_auth = $auth;
        $this->_perm = $perm;
        $this->_languageId = $lang;
        $this->_clientId = $client;
    }

    /**
     * Generates refresh JavaScript for article search form in left_top
     *
     * @param array $aValues The form values to pass to the search form
     * @return string
     */
    public function generateJs(array $aValues): string
    {
        if (!empty($aValues)) {
            // Array to map search values to search form field names
            $searchFormMap = [
                'save_title' => 'bs_search_text',
                'save_id' => 'bs_search_id',
                'save_date_field' => 'bs_search_date_type',
                'save_author' => 'bs_search_author',
                'save_date_from_year' => 'bs_search_date_from_year',
                'save_date_from_month' => 'bs_search_date_from_month',
                'save_date_from_day' => 'bs_search_date_from_day',
                'save_date_to_year' => 'bs_search_date_to_year',
                'save_date_to_month' => 'bs_search_date_to_month',
                'save_date_to_day' => 'bs_search_date_to_day',
            ];

            $formValues = [];
            foreach ($searchFormMap as $valKey => $formVal) {
                $formValues[$formVal] = $aValues[$valKey] ?? '';
            }
            $json = json_encode($formValues);

            return '
                /**
                 * Refreshes the search form in "left_top" frame.
                 * This function is also called from "left_top" frame during initialization.
                 */
                function refreshArticleSearchForm(refresh) {
                    var data = ' . $json . ';
                    var oFrame = Con.getFrame("left_top");
                    if (oFrame) {
                        if (typeof oFrame.refreshSearchForm === "function") {
                            oFrame.refreshSearchForm(data);
                        }
                    }
                }
                // Initial call to refresh the form in left_top frame
                refreshArticleSearchForm();
                ';
        } else {
            return '';
        }
    }

    /**
     * Searches in properties
     * @param int $itemidReq Property item id
     * @param int $itemtypeReq Property item type
     * @return array
     * @throws cDbException|cException
     */
    public function getSearchResults(int $itemidReq, int $itemtypeReq): array
    {
        $retValue = [];

        // Request from DB
        $propertyCollection = new cApiPropertyCollection();
        $results = $propertyCollection->getValuesByType($itemtypeReq, $itemidReq, 'savedsearch');

        // Put results in returning Array
        $retValue['save_title'] = $results['save_title'];
        $retValue['save_id'] = $results['save_id'];
        $retValue['save_date_field'] = $results['save_date_field'];
        $retValue['save_author'] = $results['save_author'];

        // Date from is stored in format 'Y-m-d 00:00:00', split it to its parts
        if (!empty($results['save_date_from'])) {
            $saveDateFrom = str_replace(' 00:00:00', '', $results['save_date_from']);
            $saveDateFromParts = explode('-', $saveDateFrom);
            if (count($saveDateFromParts) == 3) {
                $retValue['save_date_from_year'] = $saveDateFromParts[0];
                $retValue['save_date_from_month'] = cDate::padMonth($saveDateFromParts[1]);
                $retValue['save_date_from_day'] = cDate::padDay($saveDateFromParts[2]);
            }
        }

        // Date to is stored in format 'Y-m-d 23:59:59', split it to its parts
        if (!empty($results['save_date_to'])) {
            $saveDateTo = str_replace(' 23:59:59', '', $results['save_date_to']);
            $saveDateToParts = explode('-', $saveDateTo);
            if (count($saveDateToParts) == 3) {
                $retValue['save_date_to_year'] = $saveDateToParts[0];
                $retValue['save_date_to_month'] = cDate::padMonth($saveDateToParts[1]);
                $retValue['save_date_to_day'] = cDate::padDay($saveDateToParts[2]);
            }
        }
        return $retValue;
    }

    /**
     * Composes a date in format 'Y-m-d 00:00:00'.
     *
     * @param array $data Search data with save_date_from_* fields
     * @return string
     */
    public function composeSaveDateFrom(array $data): string
    {
        $fields = ['save_date_from_day', 'save_date_from_month', 'save_date_from_year'];
        foreach ($fields as $field) {
            if (empty($data[$field]) || cSecurity::toInteger($data[$field]) <= 0) {
                return '';
            }
        }
        $data['save_date_from_month'] = cDate::padMonth(cSecurity::toString($data['save_date_from_month']));
        $data['save_date_from_day'] = cDate::padDay(cSecurity::toString($data['save_date_from_day']));

        return "{$data['save_date_from_year']}-{$data['save_date_from_month']}-{$data['save_date_from_day']} 00:00:00";
    }

    /**
     * Composes a date in format 'Y-m-d 23:59:59'.
     *
     * @param array $data Search data with save_date_to_* fields
     * @return string
     */
    public function composeSaveDateTo(array $data): string
    {
        $fields = ['save_date_to_day', 'save_date_to_month', 'save_date_to_year'];
        foreach ($fields as $field) {
            if (empty($data[$field]) || cSecurity::toInteger($data[$field]) <= 0) {
                return '';
            }
        }
        $data['save_date_to_month'] = cDate::padMonth($data['save_date_to_month']);
        $data['save_date_to_day'] = cDate::padDay($data['save_date_to_day']);

        return "{$data['save_date_to_year']}-{$data['save_date_to_month']}-{$data['save_date_to_day']} 23:59:59";
    }

    /**
     * Masks string for inserting into SQL statement.
     *
     * @param string $sString
     * @return string
     */
    public function mask(string $sString): string
    {
        $sString = $this->_db->escape($sString);
        $sString = str_replace('\\', '\\\\', $sString);
        $sString = str_replace('\'', '\\\'', $sString);
        return str_replace('"', '\\"', $sString);
    }

    /**
     * Collects the article information, maps idartlang to idcats.
     *
     * @param cDb $db Database instance where the select statement to find
     *      the articles were already  executed.
     * @throws cDbException|cInvalidArgumentException
     */
    public function initializeArticleInfos(cDb $db)
    {
        // Move the cursor to the beginning
        $db->seek(0);

        $infos = [];
        while ($db->nextRecord()) {
            $idartlang = cSecurity::toInteger($db->f('idartlang'));
            $idcat = cSecurity::toInteger($db->f('idcat'));
            if (!isset($infos[$idartlang])) {
                $infos[$idartlang] = [
                    'idcats' => [],
                ];
            }
            if (!in_array($idcat, $infos[$idartlang]['idcats'])) {
                $infos[$idartlang]['idcats'][] = $idcat;
            }
        }

        $this->_articleInfos = $infos;

        // Move the cursor again to the beginning to let the
        // caller of this function do its job.
        $db->seek(0);
    }

    /**
     * Returns all category ids being collected in a previous call of
     * {@see cBackendSearchHelper::initializeArticleInfos()}.
     *
     * @return array|int[]
     */
    public function getCategoryIds(): array
    {
        if (!isset($this->_categoryIds)) {
            $ids = [];
            foreach ($this->_articleInfos as $item) {
                $ids = array_merge($ids, $item['idcats']);
            }
            $this->_categoryIds = array_values(array_unique($ids));
        }
        return $this->_categoryIds;
    }

    /**
     * Returns the article template info array.
     * Will be used, if the article has not its own template configuration.
     *
     * @param int $idcat
     * @return array
     * @throws cDbException|cInvalidArgumentException
     */
    public function getCategoryTemplateInfos(int $idcat): array
    {
        if (!isset($this->_categoryTemplateInfos)) {
            $this->_categoryTemplateInfos = [];

            $in = implode(', ', $this->getCategoryIds());

            $sql = "-- cBackendSearchHelper->_getCategoryTemplateInfos()
                SELECT
                    c.idtpl       AS idtpl,
                    c.name        AS name,
                    c.description AS description,
                    b.idtplcfg    AS idtplcfg,
                    b.idcat       AS idcat
                FROM
                    " . cRegistry::getDbTableName('tpl_conf') . " AS a,
                    " . cRegistry::getDbTableName('cat_lang') . " AS b,
                    " . cRegistry::getDbTableName('tpl') . "      AS c
                WHERE
                    b.idcat    IN (" . $in . ") AND
                    b.idlang   = " . $this->_languageId . " AND
                    b.idtplcfg = a.idtplcfg AND
                    c.idtpl    = a.idtpl AND
                    c.idclient = " . $this->_clientId;
            $this->_db->query($sql);
            while ($this->_db->nextRecord()) {
                $_idcat = cSecurity::toInteger($this->_db->f('idcat'));
                $idtplcfg = cSecurity::toInteger($this->_db->f('idtplcfg'));
                $this->_categoryTemplateInfos[$_idcat] = [
                    'idtplcfg' => $idtplcfg,
                    'idtpl' => cSecurity::toInteger($this->_db->f('idtpl')),
                    'name' => $this->_db->f('name'),
                    'description' => $this->_db->f('description') ?? '',
                ];
            }
        }

        return $this->_categoryTemplateInfos[$idcat] ?? [];
    }

    /**
     * Returns the category breadcrumb (category path).
     *
     * @param int $idcat
     * @return string
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getCategoryBreadcrumb(int $idcat): string
    {
        if (!isset($this->_categoryBreadcrumb)) {
            $this->_categoryBreadcrumb = [];
        }

        if (!isset($this->_categoryBreadcrumb[$idcat])) {
            $this->_categoryBreadcrumb[$idcat] = '';
            conCreateLocationString($idcat, '&nbsp;/&nbsp;', $this->_categoryBreadcrumb[$idcat]);
        }

        return $this->_categoryBreadcrumb[$idcat];
    }

    /**
     * Checks if the user has common permissions for content and article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasCommonContentPermission(): bool
    {
        $bCheckRights = false;
        if (!isset($this->_hasCommonContentPermission)) {
            foreach (self::COMMON_CON_AND_ARTICLE_RIGHTS as $entry) {
                $bCheckRights = $this->_perm->have_perm_area_action($entry[0], $entry[0]);
                if ($bCheckRights) {
                    break;
                }
            }

            $this->_hasCommonContentPermission = $bCheckRights;
        }

        return $this->_hasCommonContentPermission;
    }

    /**
     * Checks if the user has common permissions for content and article for a specific category.
     *
     * @param int $idcat Id of the category to check the rights for
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticlePermission(int $idcat): bool
    {
        if (!isset($this->_articlePermissions)) {
            // It seems that initializeArticleInfos() was not called before!
            if (!is_array($this->_articleInfos)) {
                return false;
            }

            $this->_articlePermissions = [];

            // Get all groups of the user
            $groups = $this->_perm->getGroupsForUser($this->_auth->auth['uid']);
            $groups[] = $this->_auth->auth['uid'];

            // Fetch rights for collected categories
            $rightsCollection = new cApiRightCollection();
            $rightsCollection->addResultField('idcat');
            $rightsCollection->setWhere('idclient', $this->_clientId);
            $rightsCollection->setWhere('idlang', $this->_languageId);
            $rightsCollection->setWhere('idcat', $this->getCategoryIds(), 'IN');
            $rightsCollection->setWhere('user_id', $groups, 'IN');
            $rightsCollection->query();
            foreach ($rightsCollection->fetchTable(['idcat' => 'idcat']) as $entry) {
                $_idcat = cSecurity::toInteger($entry['idcat']);
                if (!isset($this->_articlePermissions[$_idcat])) {
                    // First initialize with null, we'll change this later to boolean status
                    $this->_articlePermissions[$_idcat] = null;
                }
            }
        }

        // Now check for category rights
        if (is_null($this->_articlePermissions[$idcat])) {
            $bCheckRights = false;
            foreach (self::COMMON_CON_AND_ARTICLE_RIGHTS as $entry) {
                $bCheckRights = $this->_perm->have_perm_area_action_item($entry[0], $entry[0], $idcat);
                if ($bCheckRights) {
                    break;
                }
            }

            $this->_articlePermissions[$idcat] = $bCheckRights;
        }

        return is_bool($this->_articlePermissions[$idcat]) && $this->_articlePermissions[$idcat];
    }

    /**
     * Checks if the user has permission to make an article a start article.
     *
     * @param int $idcat Id of the category to check the rights for
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleMakeStartPermission(int $idcat): bool
    {
        if (!isset($this->_hasArticleMakeStartPermission)) {
            $this->_hasArticleMakeStartPermission = [];
        }
        if (!isset($this->_hasArticleMakeStartPermission[$idcat])) {

            $this->_hasArticleMakeStartPermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con', 'con_makestart', $idcat
            );
        }

        return $this->_hasArticleMakeStartPermission[$idcat];
    }

    /**
     * Checks if the user has permission to edit article content.
     *
     * @param int $idcat Id of the category to check the rights for
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleEditContentPermission(int $idcat): bool
    {
        if (!isset($this->_hasArticleEditContentPermission)) {
            $this->_hasArticleEditContentPermission = [];
        }
        if (!isset($this->_hasArticleEditContentPermission[$idcat])) {
            $this->_hasArticleEditContentPermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con_editcontent', 'con_editart', $idcat
            );
        }

        return $this->_hasArticleEditContentPermission[$idcat];
    }

    /**
     * Checks if the user has permission to duplicate article.
     *
     * @param int $idcat Id of the category to check the rights for
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleDuplicatePermission(int $idcat): bool
    {
        if (!isset($this->_hasArticleDuplicatePermission)) {
            $this->_hasArticleDuplicatePermission = [];
        }
        if (!isset($this->_hasArticleDuplicatePermission[$idcat])) {
            $this->_hasArticleDuplicatePermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con', 'con_duplicate', $idcat
            );
        }

        return $this->_hasArticleDuplicatePermission[$idcat];
    }

    /**
     * Checks if the user has permission to delete article.
     *
     * @param int $idcat Id of the category to check the rights for
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleDeletePermission(int $idcat): bool
    {
        if (!isset($this->_hasArticleDeletePermission)) {
            $this->_hasArticleDeletePermission = [];
        }
        if (!isset($this->_hasArticleDeletePermission[$idcat])) {
            $this->_hasArticleDeletePermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con', 'con_deleteart', $idcat
            );
        }

        return $this->_hasArticleDeletePermission[$idcat];
    }

}