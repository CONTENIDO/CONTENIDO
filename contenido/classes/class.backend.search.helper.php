<?php

/**
 * This file contains the backend search helper class.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purç <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
 * @package Core
 * @subpackage Backend
 */
class cBackendSearchHelper {

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
     * @var string
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
     * @var bool
     */
    protected $_hasArticleEditContentPermission;

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
    public function __construct(cDb $db, cAuth $auth, cPermission $perm, $lang, $client) {
        $this->_db = $db;
        $this->_auth = $auth;
        $this->_perm = $perm;
        $this->_languageId = cSecurity::toInteger($lang);
        $this->_clientId = cSecurity::toInteger($client);
    }

    /**
     * Generating refresh JavaScript for form in left_top
     * @global string $sSaveTitle
     * @global string $sSaveId
     * @global string $sSaveDateFromYear
     * @global string $sSaveDateFromMonth
     * @global string $sSaveDateFromDay
     * @global string $sSaveDateToYear
     * @global string $sSaveDateToMonth
     * @global string $sSaveDateToDay
     * @global string $sSaveDateField
     * @global string $sSaveAuthor
     * @global string $sSaveName
     * @param array $aValues
     * @return string
     */
    public function generateJs($aValues) {
        if (is_array($aValues)) {
            global $sSaveTitle;
            global $sSaveId;
            global $sSaveDateFromYear;
            global $sSaveDateFromMonth;
            global $sSaveDateFromDay;
            global $sSaveDateToYear;
            global $sSaveDateToMonth;
            global $sSaveDateToDay;
            global $sSaveDateField;
            global $sSaveAuthor;
            global $sSaveName;

            return 'function refreshArticleSearchForm(refresh) {
                    var oFrame = Con.getFrame("left_top");
                    if (oFrame) {
                        oForm = oFrame.document.backend_search;

                        oForm.bs_search_text.value = "' . $aValues[$sSaveTitle] . '";
                        oForm.bs_search_id.value = "' . $aValues[$sSaveId] . '";
                        oForm.bs_search_date_type.value = "' . $aValues[$sSaveDateField] . '";

                        oFrame.toggle_tr_visibility("tr_date_from");
                        oFrame.toggle_tr_visibility("tr_date_to");

                        oForm.bs_search_date_from_day.value = "' . $aValues[$sSaveDateFromDay] . '";
                        oForm.bs_search_date_from_month.value = "' . $aValues[$sSaveDateToMonth] . '";
                        oForm.bs_search_date_from_year.value = "' . $aValues[$sSaveDateFromYear] . '";

                        oForm.bs_search_date_to_day.value = "' . $aValues[$sSaveDateToDay] . '";
                        oForm.bs_search_date_to_month.value = "' . $aValues[$sSaveDateToMonth] . '";
                        oForm.bs_search_date_to_year.value = "' . $aValues[$sSaveDateToYear] . '";

                        oForm.bs_search_author.value = "' . $aValues[$sSaveAuthor] . '";
                    }
                }
                refreshArticleSearchForm();
                ';
        } else {
            return false;
        }
    }

    /**
     * Searches in properties
     * @param mixed  $itemidReq Property item id
     * @param string $itemtypeReq Property item type
     * @return array
     */
    public function getSearchResults($itemidReq, $itemtypeReq) {
        global $sSaveTitle;
        global $sSaveId;
        global $sSaveDateFrom;
        global $sSaveDateFromYear;
        global $sSaveDateFromMonth;
        global $sSaveDateFromDay;
        global $sSaveDateTo;
        global $sSaveDateToYear;
        global $sSaveDateToMonth;
        global $sSaveDateToDay;
        global $sSaveDateField;
        global $sSaveAuthor;
        global $sSaveName;
        global $sType;

        $retValue = [];
        // Request from DB
        $propertyCollection = new cApiPropertyCollection();
        $results = $propertyCollection->getValuesByType($itemtypeReq, $itemidReq, $sType);

        // Put results in returning Array
        $retValue[$sSaveTitle] = $results[$sSaveTitle];
        $retValue[$sSaveId] = $results[$sSaveId];
        $retValue[$sSaveDateField] = $results[$sSaveDateField];
        $retValue[$sSaveAuthor] = $results[$sSaveAuthor];

        // Date from
        $sSearchStrDateFromDayTmp = 0;
        $sSearchStrDateFromMonthTmp = 0;
        $sSearchStrDateFromYearTmp = 0;
        $saveDateFrom = $results[$sSaveDateFrom];
        if (isset($saveDateFrom) && sizeof($saveDateFrom) > 0) {
            $saveDateFrom = str_replace(' 00:00:00', '', $saveDateFrom);
            $saveDateFromParts = explode('-', $saveDateFrom);
            if (sizeof($saveDateFromParts) == 3) {
                $retValue[$sSaveDateFromYear] = $saveDateFromParts[0];
                $retValue[$sSaveDateFromMonth] = $saveDateFromParts[1];
                $retValue[$sSaveDateFromDay] = $saveDateFromParts[2];
            }
        }
        // Date to
        $sSearchStrDateToDayTmp = 0;
        $sSearchStrDateToMonthTmp = 0;
        $sSearchStrDateToYearTmp = 0;
        $saveDateTo = $results[$sSaveDateTo];
        if (isset($saveDateTo) && sizeof($saveDateTo) > 0) {
            $saveDateTo = str_replace(' 23:59:59', '', $saveDateTo);
            $saveDateToParts = explode('-', $saveDateTo);
            if (sizeof($saveDateToParts) == 3) {
                $retValue[$sSaveDateToYear] = $saveDateToParts[0];
                $retValue[$sSaveDateToMonth] = $saveDateToParts[1];
                $retValue[$sSaveDateToDay] = $saveDateToParts[2];
            }
        }
        return $retValue;
    }

    /**
     * Masks string for inserting into SQL statement
     * @param string $sString
     * @return string
     */
    public function mask($sString) {
        $sString = $this->_db->escape($sString);
        $sString = str_replace('\\', '\\\\', $sString);
        $sString = str_replace('\'', '\\\'', $sString);
        return str_replace('"', '\\"', $sString);
    }

    public function hasCommonContentPermission() {
        if (!isset($this->_hasCommonContentPermission)) {
            $rights = [
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

            foreach ($rights as $entry) {
                $bCheckRights = $this->_perm->have_perm_area_action($entry[0], $entry[0]);
                if ($bCheckRights) {
                    break;
                }
            }

            $this->_hasCommonContentPermission = $bCheckRights;
        }

        return $this->_hasCommonContentPermission;
    }

    public function hasArticlePermission($idcat) {
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
                    $this->_articlePermissions[$_idcat] = null;
                }
            }
        }

        // Now check for category rights
        if (is_null($this->_articlePermissions[$idcat])) {
            $rights = [
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

            foreach ($rights as $entry) {
                $bCheckRights = $this->_perm->have_perm_area_action_item($entry[0], $entry[0], $idcat);
                if ($bCheckRights) {
                    break;
                }
            }

            $this->_articlePermissions[$idcat] = $bCheckRights;
        }

        return $this->_articlePermissions[$idcat];
    }

    public function initializeArticleInfos(cDb $db) {
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

        $db->seek(0);
    }

    public function getCategoryIds() {
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
     * @return array
     * @throws cDbException|cInvalidArgumentException
     */
    public function getCategoryTemplateInfos($idcat) {
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
                    'description' => $this->_db->f('description'),
                ];
            }
        }

        return $this->_categoryTemplateInfos[$idcat] ?? [];
    }

    /**
     * Returns the category breadcrumb (category path).
     *
     * @return string
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getCategoryBreadcrumb($idcat) {
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
     * Checks if the user has permission to make an article a start article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleMakeStartPermission($idcat) {
        if (!isset($this->_hasArticleMakeStartPermission)) {
            $this->_hasArticleMakeStartPermission = [];
        }
        if (!isset($this->_hasArticleMakeStartPermission[$idcat])) {

            $this->_hasArticleMakeStartPermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con', 'con_makestart', $idcat
            );
        }

        return $this->_hasArticleMakeStartPermission;
    }

    /**
     * Checks if the user has permission to edit article content.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleEditContentPermission($idcat) {
        if (!isset($this->_hasArticleEditContentPermission)) {
            $this->_hasArticleEditContentPermission = [];
        }
        if (!isset($this->_hasArticleEditContentPermission[$idcat])) {
            $this->_hasArticleEditContentPermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con_editcontent', 'con_editart', $idcat
            );
        }

        return $this->_hasArticleEditContentPermission;
    }

    /**
     * Checks if the user has permission to duplicate article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleDuplicatePermission($idcat) {
        if (!isset($this->_hasArticleDuplicatePermission)) {
            $this->_hasArticleDuplicatePermission = [];
        }
        if (!isset($this->_hasArticleDuplicatePermission[$idcat])) {
            $this->_hasArticleDuplicatePermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con', 'con_duplicate', $idcat
            );
        }

        return $this->_hasArticleDuplicatePermission;
    }

    /**
     * Checks if the user has permission to delete article.
     *
     * @return bool
     * @throws cDbException|cException
     */
    public function hasArticleDeletePermission($idcat) {
        if (!isset($this->_hasArticleDeletePermission)) {
            $this->_hasArticleDeletePermission = [];
        }
        if (!isset($this->_hasArticleDeletePermission[$idcat])) {
            $this->_hasArticleDeletePermission[$idcat] = $this->_perm->have_perm_area_action_item(
                'con', 'con_deleteart', $idcat
            );
        }

        return $this->_hasArticleDeletePermission;
    }

}