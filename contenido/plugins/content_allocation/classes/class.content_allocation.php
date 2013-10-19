<?php
/**
 * This file contains the main class for the plugin content allocation.
 *
 * @package Plugin
 * @subpackage ContentAllocation
 * @version SVN Revision $Rev:$
 *
 * @author Marco Jahn
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Main class for content allocation
 *
 * @package Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocation {

    /**
     * References database object
     *
     * @var cDb
     */
    var $db = NULL;

    var $table = NULL;

    var $lang = NULL;

    var $client = NULL;

    var $treeObj = NULL;

    /**
     * @modified 27.10.2005 new class variable $this->bDebug (if true print
     * debug information)
     */
    function pApiContentAllocation() {
        global $db, $cfg, $lang, $client;

        $this->db = cRegistry::getDb();
        $this->table = $cfg['tab'];
        $this->lang = $lang;
        $this->client = $client;

        // use this option carefully and only temporary.
        // the hidden debug output as html-comments can cause display problems.
        $this->bDebug = false;

        $this->treeObj = new pApiTree('f31a4384-e5c1-4ede-b1bb-f43657ec73a5');
    }

    function storeAllocations($idartlang, $allocations) {
        // empty before insert
        $this->deleteAllocationsByIdartlang($idartlang);

        if (is_array($allocations)) {
            foreach ($allocations as $value) {
                $sql = "INSERT INTO " . $this->table['pica_alloc_con'] . " (idpica_alloc, idartlang) VALUES (" . cSecurity::toInteger($value) . ", " . cSecurity::toInteger($idartlang) . ")";
                $this->db->query($sql);
            }
        }
    }

    function deleteAllocations($idpica_alloc) {
        $sql = "DELETE FROM " . $this->table['pica_alloc_con'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc);
        $this->db->query($sql);
    }

    function deleteAllocationsByIdartlang($idartlang) {
        $sql = "DELETE FROM " . $this->table['pica_alloc_con'] . " WHERE idartlang = " . cSecurity::toInteger($idartlang);
        $this->db->query($sql);
    }

    /**
     * load all tagging keys
     *
     * @param unknown_type $idartlang
     * @return multitype:Ambigous <mixed, unknown>
     */
    function loadAllocations($idartlang) {
        $this->db->query("-- pApiContentAllocation->loadAllocations()
            SELECT
                a.idpica_alloc
            FROM
                `{$this->table['pica_alloc']}` AS a
                , `{$this->table['pica_alloc_con']}` AS b
            WHERE
                idartlang = $idartlang
                AND a.idpica_alloc=b.idpica_alloc
            ;");

        $result = array();
        while ($this->db->nextRecord()) {
            $result[] = $this->db->f('idpica_alloc');
        }

        return $result;
    }

    function loadAllocationsWithNames($idartlang, $parent, $firstonly = false) {
        global $cfg;

        $sql = "SELECT " . $cfg['tab']['pica_alloc'] . ".idpica_alloc FROM " . $cfg['tab']['pica_alloc'] . "
                    INNER JOIN " . $cfg['tab']['pica_alloc_con'] . " ON
                    " . $cfg['tab']['pica_alloc'] . ".idpica_alloc = " . $cfg['tab']['pica_alloc_con'] . ".idpica_alloc
                    WHERE (" . $cfg['tab']['pica_alloc'] . ".parentid = " . cSecurity::toInteger($parent) . ") AND (" . $cfg['tab']['pica_alloc_con'] . ".idartlang=" . cSecurity::toInteger($idartlang) . ")
                    ORDER BY " . $cfg['tab']['pica_alloc'] . ".sortorder";

        $this->db->query($sql);

        while ($this->db->nextRecord()) {
            $tmp[$this->db->f("idpica_alloc")] = $this->treeObj->_fetchItemNameLang($this->db->f("idpica_alloc"));

            if ($firstonly) {
                break;
            }
        }

        return $tmp;
    }

    /**
     * Build query to find matching content by ContentAllocation
     *
     * @param array $restrictions
     * @return string SQL
     *         @modified 17.11.2005 by Willi Man
     */
    function findMatchingContent($restrictions = NULL, $max = 0) {
        if (!is_array($restrictions)) {
            return false;
        }

        global $aCategoriesToExclude; // @see config.local.php!
        $sql = $this->_buildQuery($restrictions, $aCategoriesToExclude, $max);

        return $sql;
    }

    /**
     * Build query to find matching content by ContentAllocation
     *
     * @param array $restrictions
     * @return string SQL
     */
    function _buildQuery($restrictions, $aCategoriesToExclude, $max) {
        global $cfg;

        $size = sizeof($restrictions);

        if ($size == 0) {
            return '';
        }

        $sql_concat = unserialize('a:78:{i:0;s:2:"aa";i:1;s:2:"ab";i:2;s:2:"ac";i:3;s:2:"ad";i:4;s:2:"ae";i:5;s:2:"af";i:6;s:2:"ag";i:7;s:2:"ah";i:8;s:2:"ai";i:9;s:2:"aj";i:10;s:2:"ak";i:11;s:2:"al";i:12;s:2:"am";i:13;s:2:"an";i:14;s:2:"ao";i:15;s:2:"ap";i:16;s:2:"aq";i:17;s:2:"ar";i:18;s:2:"as";i:19;s:2:"at";i:20;s:2:"au";i:21;s:2:"av";i:22;s:2:"aw";i:23;s:2:"ax";i:24;s:2:"ay";i:25;s:2:"az";i:26;s:2:"ca";i:27;s:2:"cb";i:28;s:2:"cc";i:29;s:2:"cd";i:30;s:2:"ce";i:31;s:2:"cf";i:32;s:2:"cg";i:33;s:2:"ch";i:34;s:2:"ci";i:35;s:2:"cj";i:36;s:2:"ck";i:37;s:2:"cl";i:38;s:2:"cm";i:39;s:2:"cn";i:40;s:2:"co";i:41;s:2:"cp";i:42;s:2:"cq";i:43;s:2:"cr";i:44;s:2:"cs";i:45;s:2:"ct";i:46;s:2:"cu";i:47;s:2:"cv";i:48;s:2:"cw";i:49;s:2:"cx";i:50;s:2:"cy";i:51;s:2:"cz";i:52;s:1:"a";i:53;s:1:"b";i:54;s:1:"c";i:55;s:1:"d";i:56;s:1:"e";i:57;s:1:"f";i:58;s:1:"g";i:59;s:1:"h";i:60;s:1:"i";i:61;s:1:"j";i:62;s:1:"k";i:63;s:1:"l";i:64;s:1:"m";i:65;s:1:"n";i:66;s:1:"o";i:67;s:1:"p";i:68;s:1:"q";i:69;s:1:"r";i:70;s:1:"s";i:71;s:1:"t";i:72;s:1:"u";i:73;s:1:"v";i:74;s:1:"w";i:75;s:1:"x";i:76;s:1:"y";i:77;s:1:"z";}');

        $sqlTemplate = "SELECT cal.idart, cal.online, aa.idartlang, cat.idcat FROM {TABLES} WHERE {WHERE} ";

        $tables = array();
        $where = array();

        for ($i = 0; $i < $size; $i++) {
            if ($i == 0) { // first
                $tables[] = " " . $cfg['tab']['pica_alloc_con'] . " AS " . $sql_concat[$i];
            } else {
                $tables[] = " LEFT JOIN " . $cfg['tab']['pica_alloc_con'] . " AS " . $sql_concat[$i] . " USING (idartlang)";
            }
            if (is_int((int) $restrictions[$i]) and $restrictions[$i] > 0) {
                $where[] = $sql_concat[$i] . ".idpica_alloc = " . $restrictions[$i];
            }
        }

        // fetch only articles which are online
        $where[] = 'cal.online = 1';

        // fetch only articles which are not in following categories
        if (count($aCategoriesToExclude) > 0) {
            $where[] = "cat.idcat NOT IN (" . implode(',', $aCategoriesToExclude) . ")";
        }

        // join art_lang for idart
        $tables[] = " LEFT JOIN " . $this->table['art_lang'] . " AS cal USING (idartlang)";
        $tables[] = " LEFT JOIN " . $this->table['cat_art'] . " AS cart USING (idart)";
        $tables[] = " LEFT JOIN " . $this->table['cat'] . " as cat USING (idcat)";

        $tables = implode('', $tables);
        $where = implode(' AND ', $where);

        $sql = str_replace('{TABLES}', $tables, $sqlTemplate);
        $sql = str_replace('{WHERE}', $where, $sql);

        $sql .= " ORDER BY cal.published DESC";

        if ($max != 0 && is_integer($max)) {
            $sql .= " LIMIT " . $max;
        }

        if ($this->bDebug) {
            print "<!-- ";
            print $sql;
            print " -->";
        } // @modified
                                                                      // 27.10.2005

        return $sql;
    }

    /**
     * Search articles by ContentAllocation and catgories
     *
     * @param array $aContentAllocation
     * @param array $aCategories
     *
     * @return array of articles
     */
    function findMatchingContentByContentAllocationByCategories($aContentAllocation, $aCategories = array(), $iOffset = 0, $iNumOfRows = 0) {
        if (!is_array($aContentAllocation) || count($aContentAllocation) == 0) {
            return array();
        }

        for ($i = 0; $i < count($aContentAllocation); $i++) {
            if (!is_int((int) $aContentAllocation[$i]) or !$aContentAllocation[$i] > 0) {
                return array();
            }
        }

        for ($i = 0; $i < count($aCategories); $i++) {
            if (!is_int((int) $aCategories[$i]) or !$aCategories[$i] > 0) {
                return array();
            }
        }

        $sql = $this->_buildQuery_MatchingContentByContentAllocationByCategories($aContentAllocation, $aCategories, $iOffset, $iNumOfRows);

        $this->db->query($sql);

        $aResult = array();
        while (false !== $oRow = $this->db->getResultObject()) {
            $aResult[] = $oRow;
        }
        return $aResult;
    }

    /**
     * build SQL query to find articles by ContentAllocation and catgories
     */
    function _buildQuery_MatchingContentByContentAllocationByCategories($aContentAllocation, $aCategories, $iOffset, $iNumOfRows) {
        global $cfg;

        $size = sizeof($aContentAllocation);

        $sql_concat = unserialize('a:78:{i:0;s:2:"aa";i:1;s:2:"ab";i:2;s:2:"ac";i:3;s:2:"ad";i:4;s:2:"ae";i:5;s:2:"af";i:6;s:2:"ag";i:7;s:2:"ah";i:8;s:2:"ai";i:9;s:2:"aj";i:10;s:2:"ak";i:11;s:2:"al";i:12;s:2:"am";i:13;s:2:"an";i:14;s:2:"ao";i:15;s:2:"ap";i:16;s:2:"aq";i:17;s:2:"ar";i:18;s:2:"as";i:19;s:2:"at";i:20;s:2:"au";i:21;s:2:"av";i:22;s:2:"aw";i:23;s:2:"ax";i:24;s:2:"ay";i:25;s:2:"az";i:26;s:2:"ca";i:27;s:2:"cb";i:28;s:2:"cc";i:29;s:2:"cd";i:30;s:2:"ce";i:31;s:2:"cf";i:32;s:2:"cg";i:33;s:2:"ch";i:34;s:2:"ci";i:35;s:2:"cj";i:36;s:2:"ck";i:37;s:2:"cl";i:38;s:2:"cm";i:39;s:2:"cn";i:40;s:2:"co";i:41;s:2:"cp";i:42;s:2:"cq";i:43;s:2:"cr";i:44;s:2:"cs";i:45;s:2:"ct";i:46;s:2:"cu";i:47;s:2:"cv";i:48;s:2:"cw";i:49;s:2:"cx";i:50;s:2:"cy";i:51;s:2:"cz";i:52;s:1:"a";i:53;s:1:"b";i:54;s:1:"c";i:55;s:1:"d";i:56;s:1:"e";i:57;s:1:"f";i:58;s:1:"g";i:59;s:1:"h";i:60;s:1:"i";i:61;s:1:"j";i:62;s:1:"k";i:63;s:1:"l";i:64;s:1:"m";i:65;s:1:"n";i:66;s:1:"o";i:67;s:1:"p";i:68;s:1:"q";i:69;s:1:"r";i:70;s:1:"s";i:71;s:1:"t";i:72;s:1:"u";i:73;s:1:"v";i:74;s:1:"w";i:75;s:1:"x";i:76;s:1:"y";i:77;s:1:"z";}');

        $sqlTemplate = "SELECT cal.idart, cal.online, aa.idartlang, cat.idcat, aa.idpica_alloc FROM {TABLES} WHERE {WHERE} ";

        $tables = array();
        $where = array();

        for ($i = 0; $i < $size; $i++) {
            if ($i == 0) { // first
                $tables[] = " " . $cfg['tab']['pica_alloc_con'] . " AS " . $sql_concat[$i];
            } else {
                $tables[] = " LEFT JOIN " . $cfg['tab']['pica_alloc_con'] . " AS " . $sql_concat[$i] . " USING (idartlang)";
            }
            if (is_int((int) $aContentAllocation[$i]) and $aContentAllocation[$i] > 0) {
                $where[] = $sql_concat[$i] . ".idpica_alloc = " . $aContentAllocation[$i];
            }
        }

        // fetch only articles which are online
        $where[] = 'cal.online = 1';

        // fetch only articles in following categories
        if (count($aCategories) > 0) {
            $where[] = "cat.idcat IN (" . implode(',', $aCategories) . ")";
        }

        // join art_lang for idart
        $tables[] = " LEFT JOIN " . $this->table['art_lang'] . " AS cal USING (idartlang)";
        $tables[] = " LEFT JOIN " . $this->table['cat_art'] . " AS cart USING (idart)";
        $tables[] = " LEFT JOIN " . $this->table['cat'] . " as cat USING (idcat)";

        $tables = implode('', $tables);
        $where = implode(' AND ', $where);

        $sql = str_replace('{TABLES}', $tables, $sqlTemplate);
        $sql = str_replace('{WHERE}', $where, $sql);

        $sql .= " ORDER BY cal.published DESC";

        if (is_integer($iNumOfRows) and $iNumOfRows > 0) {
            $sql .= " LIMIT " . $iOffset . ", " . $iNumOfRows;
        }

        if ($this->bDebug) {
            print "<!-- ";
            print $sql;
            print " -->";
        } // @modified
                                                                      // 27.10.2005

        return $sql;
    }

    /**
     * Search articles by catgories without start articles
     *
     * @param array $aCategories
     * @param int $iOffset
     * @param int $iNumOfRows
     * @param string $sResultType element of {article_id, object}
     *
     * @return array of articles
     */
    function findMatchingContentByCategories($aCategories = array(), $iOffset = 0, $iNumOfRows = 0, $sResultType = '') {
        for ($i = 0; $i < count($aCategories); $i++) {
            if (!is_int((int) $aCategories[$i]) or !$aCategories[$i] > 0) {
                return array();
            }
        }

        $sql = $this->_buildQuery_MatchingContentByCategories($aCategories, $iOffset, $iNumOfRows);

        $this->db->query($sql);

        $aResult = array();

        while (false !== $oRow = $this->db->getResultObject()) {
            if ($sResultType == 'article_language_id') {
                $aResult[] = $oRow->idartlang;
            } else {
                $aResult[] = $oRow;
            }
        }
        return $aResult;
    }

    /**
     * build SQL query to find articles by catgories
     */
    function _buildQuery_MatchingContentByCategories($aCategories, $iOffset, $iNumOfRows) {
        if (count($aCategories) > 0) {
            $sWHERE_Category_IN = " c.idcat IN (" . implode(',', $aCategories) . ") AND ";
        } else {
            $sWHERE_Category_IN = '';
        }
        if (is_integer($iNumOfRows) and $iNumOfRows > 0) {
            $sLimit = " LIMIT " . cSecurity::toInteger($iOffset) . ", " . cSecurity::toInteger($iNumOfRows);
        } else {
            $sLimit = '';
        }

        $sql = '
        SELECT
            a.idart, a.online, a.idartlang, c.idcat
        FROM
            ' . $this->table['art_lang'] . ' AS a,
            ' . $this->table['art'] . ' AS b,
            ' . $this->table['cat_art'] . ' AS c,
            ' . $this->table['cat_lang'] . ' AS d
        WHERE
            ' . $sWHERE_Category_IN . '
            b.idclient = ' . cSecurity::toInteger($this->client) . ' AND
            a.idlang = ' . cSecurity::toInteger($this->lang) . ' AND
            a.idartlang != d.startidartlang AND
            a.online = 1 AND
            c.idcat = d.idcat AND
            b.idart = c.idart AND
            a.idart = b.idart
            ' . $sLimit . ' ';

        if ($this->bDebug) {
            print "<!-- ";
            print $sql;
            print " -->";
        }

        return $sql;
    }

}

?>