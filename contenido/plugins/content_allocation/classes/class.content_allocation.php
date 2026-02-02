<?php

/**
 * This file contains the main class for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Main class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocation
{

    /**
     * References database object
     *
     * @var cDb
     */
    protected $_db = null;

    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * @var int
     */
    protected $_lang = 0;

    /**
     * @var int
     */
    protected $_client = 0;

    /**
     * @var object
     */
    protected $_treeObj = null;

    /**
     * pApiContentAllocation constructor
     */
    public function __construct()
    {
        $this->_db = cRegistry::getDb();
        $this->_lang = cRegistry::getLanguageId();
        $this->_client = cRegistry::getClientId();
        $this->_treeObj = new pApiTree('f31a4384-e5c1-4ede-b1bb-f43657ec73a5');
    }

    /**
     * Store allocations
     *
     * @throws cDbException
     */
    public function storeAllocations(int $idartlang, array $allocations)
    {
        // empty before insert
        $this->deleteAllocationsByIdartlang($idartlang);

        foreach ($allocations as $value) {
            $sql = $this->_db->buildInsert(
                cDb::getTableName('pica_alloc_con'),
                [
                'idpica_alloc' => $value,
                'idartlang' => $idartlang,
                ]
            );
            $this->_db->query($sql);
        }
    }

    /**
     * Delete allocations by allocation id
     *
     * @throws cDbException
     */
    public function deleteAllocations(int $idpica_alloc)
    {
        $this->_db->query(
            'DELETE FROM `%s` WHERE `idpica_alloc` = %d',
            cDb::getTableName('pica_alloc_con'),
            $idpica_alloc
        );
    }

    /**
     * Delete allocations by language id
     *
     * @throws cDbException
     */
    public function deleteAllocationsByIdartlang(int $idartlang)
    {
        $this->_db->query(
            'DELETE FROM `%s` WHERE `idartlang` = %d',
            cDb::getTableName('pica_alloc_con'),
            $idartlang
        );
    }

    /**
     * Load all tagging keys
     *
     * @return int[] List of allocation ids
     * @throws cDbException
     */
    public function loadAllocations(int $idartlang): array
    {
        $sql = "-- pApiContentAllocation->loadAllocations()
            SELECT
                a.idpica_alloc
            FROM
                `%s` AS a, `%s` AS b
            WHERE
                `idartlang` = %d AND a.idpica_alloc = b.idpica_alloc
            ;";

        $this->_db->query($sql, cDb::getTableName('pica_alloc'), cDb::getTableName('pica_alloc_con'), $idartlang);

        $result = [];
        while ($this->_db->nextRecord()) {
            $result[] = cSecurity::toInteger($this->_db->f('idpica_alloc'));
        }

        return $result;
    }

    /**
     * Load allocations by language id and parent id
     *
     * @throws cDbException
     */
    public function loadAllocationsWithNames(int $idartlang, int $parent, bool $firstOnly = false): array
    {
        $sql = "SELECT :tab_pica_alloc.idpica_alloc FROM :tab_pica_alloc
            INNER JOIN :tab_pica_alloc_con ON :tab_pica_alloc.idpica_alloc = :tab_pica_alloc_con.idpica_alloc
            WHERE (:tab_pica_alloc.parentid = :parentid) AND (:tab_pica_alloc_con.idartlang = idartlang)
            ORDER BY :tab_pica_alloc.sortorder";

        if ($firstOnly) {
            $sql .= ' LIMIT 1';
        }

        $params = [
            'tab_pica_alloc_con' => cDb::getTableName('pica_alloc_con'),
            'tab_pica_alloc' => cDb::getTableName('pica_alloc'),
            'parentid' => cSecurity::toInteger($parent),
            'idartlang' => cSecurity::toInteger($idartlang)
        ];

        $this->_db->query($sql, $params);

        $result = [];
        while ($this->_db->nextRecord()) {
            $idPicaAlloc = cSecurity::toInteger($this->_db->f('idpica_alloc'));
            $result[$idPicaAlloc] = $this->_treeObj->fetchItemNameLang($idPicaAlloc);
        }

        return $result;
    }

    /**
     * Build query to find matching content by ContentAllocation
     *
     * @param ?array $restrictions [optional]
     * @return string|bool $sql or false
     */
    public function findMatchingContent(?array $restrictions = null, int $max = 0)
    {
        if (!is_array($restrictions)) {
            return false;
        }

        global $aCategoriesToExclude; // @see config.local.php!
        return $this->_buildQuery($restrictions, $aCategoriesToExclude ?? [], $max);
    }

    /**
     * Build query to find matching content by ContentAllocation
     */
    protected function _buildQuery(array $restrictions, array $categoriesToExclude, int $max): string
    {
        $size = count($restrictions);
        if ($size == 0) {
            return '';
        }

        $sql_concat = unserialize('a:78:{i:0;s:2:"aa";i:1;s:2:"ab";i:2;s:2:"ac";i:3;s:2:"ad";i:4;s:2:"ae";i:5;s:2:"af";i:6;s:2:"ag";i:7;s:2:"ah";i:8;s:2:"ai";i:9;s:2:"aj";i:10;s:2:"ak";i:11;s:2:"al";i:12;s:2:"am";i:13;s:2:"an";i:14;s:2:"ao";i:15;s:2:"ap";i:16;s:2:"aq";i:17;s:2:"ar";i:18;s:2:"as";i:19;s:2:"at";i:20;s:2:"au";i:21;s:2:"av";i:22;s:2:"aw";i:23;s:2:"ax";i:24;s:2:"ay";i:25;s:2:"az";i:26;s:2:"ca";i:27;s:2:"cb";i:28;s:2:"cc";i:29;s:2:"cd";i:30;s:2:"ce";i:31;s:2:"cf";i:32;s:2:"cg";i:33;s:2:"ch";i:34;s:2:"ci";i:35;s:2:"cj";i:36;s:2:"ck";i:37;s:2:"cl";i:38;s:2:"cm";i:39;s:2:"cn";i:40;s:2:"co";i:41;s:2:"cp";i:42;s:2:"cq";i:43;s:2:"cr";i:44;s:2:"cs";i:45;s:2:"ct";i:46;s:2:"cu";i:47;s:2:"cv";i:48;s:2:"cw";i:49;s:2:"cx";i:50;s:2:"cy";i:51;s:2:"cz";i:52;s:1:"a";i:53;s:1:"b";i:54;s:1:"c";i:55;s:1:"d";i:56;s:1:"e";i:57;s:1:"f";i:58;s:1:"g";i:59;s:1:"h";i:60;s:1:"i";i:61;s:1:"j";i:62;s:1:"k";i:63;s:1:"l";i:64;s:1:"m";i:65;s:1:"n";i:66;s:1:"o";i:67;s:1:"p";i:68;s:1:"q";i:69;s:1:"r";i:70;s:1:"s";i:71;s:1:"t";i:72;s:1:"u";i:73;s:1:"v";i:74;s:1:"w";i:75;s:1:"x";i:76;s:1:"y";i:77;s:1:"z";}');

        $sqlTemplate = "SELECT cal.idart, cal.online, aa.idartlang, cat.idcat FROM {TABLES} WHERE {WHERE} ";

        $tables = [];
        $where = [];

        for ($i = 0; $i < $size; $i++) {
            if ($i == 0) { // first
                $tables[] = " " . cDb::getTableName('pica_alloc_con') . " AS " . $sql_concat[$i];
            } else {
                $tables[] = " LEFT JOIN " . cDb::getTableName('pica_alloc_con') . " AS " . $sql_concat[$i] . " USING (idartlang)";
            }
            if (is_int((int)$restrictions[$i]) and $restrictions[$i] > 0) {
                $where[] = $sql_concat[$i] . ".idpica_alloc = " . $restrictions[$i];
            }
        }

        // fetch only articles which are online
        $where[] = 'cal.online = 1';

        // fetch only articles which are not in following categories
        if (count($categoriesToExclude) > 0) {
            $where[] = "cat.idcat NOT IN (" . implode(',', $categoriesToExclude) . ")";
        }

        // join art_lang for idart
        $tables[] = " LEFT JOIN " . cDb::getTableName('art_lang') . " AS cal USING (idartlang)";
        $tables[] = " LEFT JOIN " . cDb::getTableName('cat_art') . " AS cart USING (idart)";
        $tables[] = " LEFT JOIN " . cDb::getTableName('cat') . " as cat USING (idcat)";

        $tables = implode('', $tables);
        $where = implode(' AND ', $where);

        $sql = str_replace('{TABLES}', $tables, $sqlTemplate);
        $sql = str_replace('{WHERE}', $where, $sql);

        $sql .= " ORDER BY cal.published DESC";

        if ($max > 0) {
            $sql .= " LIMIT " . $max;
        }

        if ($this->_debug) {
            print "<!-- $sql -->";
        }

        return $sql;
    }

    /**
     * Search articles by ContentAllocation and categories
     *
     * @param int[] $contentAllocationIds
     * @param int[] $categoryIds
     * @return array of articles
     * @throws cDbException
     */
    public function findMatchingContentByContentAllocationByCategories(
        array $contentAllocationIds,
        array $categoryIds = [],
        int $offset = 0,
        int $numOfRows = 0
    ): array
    {
        $contentAllocationIds = $this->sanitizeNumericArray($contentAllocationIds);
        if (!count($contentAllocationIds)) {
            return [];
        }

        $categoryIds = $this->sanitizeNumericArray($categoryIds);

        $sql = $this->_buildQuery_MatchingContentByContentAllocationByCategories(
            $contentAllocationIds,
            $categoryIds,
            $offset,
            $numOfRows
        );
        $this->_db->query($sql);

        $result = [];
        while (false !== $oRow = $this->_db->getResultObject()) {
            $result[] = $oRow;
        }

        return $result;
    }

    /**
     * Build SQL query to find articles by ContentAllocation and categories
     *
     * @param int[] $contentAllocationIds
     * @param int[] $categoryIds
     */
    protected function _buildQuery_MatchingContentByContentAllocationByCategories(
        array $contentAllocationIds,
        array $categoryIds,
        int $offset,
        int $numOfRows
    ): string
    {
        $size = count($contentAllocationIds);

        $sql_concat = unserialize('a:78:{i:0;s:2:"aa";i:1;s:2:"ab";i:2;s:2:"ac";i:3;s:2:"ad";i:4;s:2:"ae";i:5;s:2:"af";i:6;s:2:"ag";i:7;s:2:"ah";i:8;s:2:"ai";i:9;s:2:"aj";i:10;s:2:"ak";i:11;s:2:"al";i:12;s:2:"am";i:13;s:2:"an";i:14;s:2:"ao";i:15;s:2:"ap";i:16;s:2:"aq";i:17;s:2:"ar";i:18;s:2:"as";i:19;s:2:"at";i:20;s:2:"au";i:21;s:2:"av";i:22;s:2:"aw";i:23;s:2:"ax";i:24;s:2:"ay";i:25;s:2:"az";i:26;s:2:"ca";i:27;s:2:"cb";i:28;s:2:"cc";i:29;s:2:"cd";i:30;s:2:"ce";i:31;s:2:"cf";i:32;s:2:"cg";i:33;s:2:"ch";i:34;s:2:"ci";i:35;s:2:"cj";i:36;s:2:"ck";i:37;s:2:"cl";i:38;s:2:"cm";i:39;s:2:"cn";i:40;s:2:"co";i:41;s:2:"cp";i:42;s:2:"cq";i:43;s:2:"cr";i:44;s:2:"cs";i:45;s:2:"ct";i:46;s:2:"cu";i:47;s:2:"cv";i:48;s:2:"cw";i:49;s:2:"cx";i:50;s:2:"cy";i:51;s:2:"cz";i:52;s:1:"a";i:53;s:1:"b";i:54;s:1:"c";i:55;s:1:"d";i:56;s:1:"e";i:57;s:1:"f";i:58;s:1:"g";i:59;s:1:"h";i:60;s:1:"i";i:61;s:1:"j";i:62;s:1:"k";i:63;s:1:"l";i:64;s:1:"m";i:65;s:1:"n";i:66;s:1:"o";i:67;s:1:"p";i:68;s:1:"q";i:69;s:1:"r";i:70;s:1:"s";i:71;s:1:"t";i:72;s:1:"u";i:73;s:1:"v";i:74;s:1:"w";i:75;s:1:"x";i:76;s:1:"y";i:77;s:1:"z";}');

        $sqlTemplate = "SELECT cal.idart, cal.online, aa.idartlang, cat.idcat, aa.idpica_alloc FROM {TABLES} WHERE {WHERE} ";

        $tables = [];
        $where = [];

        for ($i = 0; $i < $size; $i++) {
            if ($i == 0) { // first
                $tables[] = " " . cDb::getTableName('pica_alloc_con') . " AS " . $sql_concat[$i];
            } else {
                $tables[] = " LEFT JOIN " . cDb::getTableName('pica_alloc_con') . " AS " . $sql_concat[$i] . " USING (idartlang)";
            }
            if (is_int((int)$contentAllocationIds[$i]) && $contentAllocationIds[$i] > 0) {
                $where[] = $sql_concat[$i] . ".idpica_alloc = " . $contentAllocationIds[$i];
            }
        }

        // fetch only articles which are online
        $where[] = 'cal.online = 1';

        // fetch only articles in following categories
        if (count($categoryIds) > 0) {
            $where[] = "cat.idcat IN (" . implode(',', $categoryIds) . ")";
        }

        // join art_lang for idart
        $tables[] = " LEFT JOIN " . cDb::getTableName('art_lang') . " AS cal USING (idartlang)";
        $tables[] = " LEFT JOIN " . cDb::getTableName('cat_art') . " AS cart USING (idart)";
        $tables[] = " LEFT JOIN " . cDb::getTableName('cat') . " as cat USING (idcat)";

        $tables = implode('', $tables);
        $where = implode(' AND ', $where);

        $sql = str_replace('{TABLES}', $tables, $sqlTemplate);
        $sql = str_replace('{WHERE}', $where, $sql);

        $sql .= " ORDER BY cal.published DESC";

        if ($numOfRows > 0) {
            $sql .= " LIMIT " . $offset . ", " . $numOfRows;
        }

        if ($this->_debug) {
            print "<!-- $sql -->";
        }

        return $sql;
    }

    /**
     * Search articles by categories without start articles
     *
     * @param string $resultType element of {article_id, object} [optional]
     * @return array of articles
     * @throws cDbException
     */
    public function findMatchingContentByCategories(
        array $categoryIds = [],
        int $offset = 0,
        int $numOfRows = 0,
        string $resultType = ''
    ): array {
        for ($i = 0; $i < count($categoryIds); $i++) {
            if (!is_int((int)$categoryIds[$i]) || !$categoryIds[$i] > 0) {
                return [];
            }
        }

        $sql = $this->_buildQuery_MatchingContentByCategories($categoryIds, $offset, $numOfRows);
        $this->_db->query($sql);
        $result = [];

        while (false !== $row = $this->_db->getResultObject()) {
            if ($resultType == 'article_language_id') {
                $result[] = $row->idartlang;
            } else {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Build SQL query to find articles by categories
     *
     * @param int[] $categoryIds
     */
    public function _buildQuery_MatchingContentByCategories(array $categoryIds, int $offset, int $numOfRows): string
    {
        if (count($categoryIds) > 0) {
            $whereCategoryIN = " c.idcat IN (" . implode(',', $categoryIds) . ") AND ";
        } else {
            $whereCategoryIN = '';
        }

        if ($numOfRows > 0) {
            $limit = " LIMIT " . $offset . ", " . $numOfRows;
        } else {
            $limit = '';
        }

        $sql = '
        SELECT
            a.idart, a.online, a.idartlang, c.idcat
        FROM
            ' . cDb::getTableName('art_lang') . ' AS a,
            ' . cDb::getTableName('art') . ' AS b,
            ' . cDb::getTableName('cat_art') . ' AS c,
            ' . cDb::getTableName('cat_lang') . ' AS d
        WHERE
            ' . $whereCategoryIN . '
            b.idclient = ' . cSecurity::toInteger($this->_client) . ' AND
            a.idlang = ' . cSecurity::toInteger($this->_lang) . ' AND
            a.idartlang != d.startidartlang AND
            a.online = 1 AND
            c.idcat = d.idcat AND
            b.idart = c.idart AND
            a.idart = b.idart
            ' . $limit . ' ';

        if ($this->_debug) {
            print "<!-- $sql -->";
        }

        return $sql;
    }

    /**
     * Casts values to integer and removes values <= 0.
     */
    private function sanitizeNumericArray(array $array): array
    {
        $array = array_map('intval', $array);
        return array_filter($array, function ($value) {
            return $value > 0;
        });
    }

}
