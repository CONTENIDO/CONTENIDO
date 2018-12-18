<?php
/**
 * This file contains the tree class for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Tree class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiTree {

    /**
     *
     * CONTENIDO Database
     * @var object cDb
     */
    protected $_db = null;

    /*
     *
     * @var boolean
     */
    protected $_debug = false;

    /**
     *
     * @var array
     */
    protected $_table = array();

    /**
     *
     * @var integer
     */
    protected $_lang = 1;

    /**
     *
     * @var integer
     */
    protected $_client = 1;

    /**
     *
     * @var integer
     */
    protected $_defaultLang = 1;

    /**
     *
     * @var boolean
     */
    protected $_logger = null;

    /**
     *
     * @var string
     */
    protected $_user = '';

    /**
     *
     * @var array
     */
    protected $_treeStatus = array();

    /**
     *
     * @var string
     */
    protected $_uuid = '';

    /**
     *
     * @var array
     */
    protected $_arrInFilters = array('htmlspecialchars', 'addslashes');

    /**
     *
     * @var array
     */
    protected $_arrOutFilters = array('stripslashes', 'htmldecode');

    /**
     * pApiTree constructor
     *
     * @param string $uuid
     */
    public function __construct($uuid) {
        $cfg = cRegistry::getConfig();
        $auth = cRegistry::getAuth();

        $this->_db = cRegistry::getDb();
        $this->_table = $cfg['tab'];
        $this->_lang = cRegistry::getLanguageId();
        $this->_client = cRegistry::getClientId();

        $this->_uuid = $uuid;

        $this->_user =  new cApiUser($auth->auth['uid']);
        $this->loadTreeStatus();
    }

    /**
     * Old constructor
     *
     * @deprecated [2016-02-11]
     * 				This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @param string $uuid
     * @return __construct()
     */
    public function pApiTree($uuid) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        return $this->__construct($uuid);
    }

    /**
     * Fetch tree
     *
     * @param mixed $parentId
     * @param int $level
     * @param bool $useTreeStatus (if true use expand/collapsed status of the tree, otherwise not)
     * @result array|boolean
     */
    public function fetchTree($parentId = false, $level = 0, $useTreeStatus = true) {

        // fetch current lang category
        $sql = "SELECT
                    tree.idpica_alloc, tree.parentid, tree.sortorder
                FROM
                    " . $this->_table['pica_alloc'] . " as tree";

        if ($parentId === false) { // fetch from root node
            $sql .= " WHERE tree.parentid = '0'";
        } else { // fetch by given id
            $sql .= " WHERE tree.parentid = " . cSecurity::toInteger($parentId);
        }

        $sql .= " ORDER BY sortorder ASC";

        $this->_db->query($sql);

        $result_tmp = array(); // tmp result array
        while ($this->_db->nextRecord()) { // walk resultset

            $item = $this->fetchItemNameLang($this->_db->f('idpica_alloc'));

            // If no translation founded, continue
            if ($item === false) {
            	continue;
            }

            $itemStatus = 'expanded';

            if ($useTreeStatus) { # modified 27.10.2005
                if (is_array($this->_treeStatus) && array_key_exists($this->_db->f('idpica_alloc'), $this->_treeStatus)) {
                    $itemStatus = 'collapsed';
                }
            }

            $rs = array(
                'idpica_alloc' => $this->_db->f('idpica_alloc'),
                'parentid' => ($this->_db->f('parentid') == NULL) ? false : $this->_db->f('parentid'),
                'sortorder' => $this->_db->f('sortorder'),
                'name' => $this->_outFilter($item['name']),
                'idlang' => $item['idlang'],
                'level' => $level,
                'status' => $itemStatus,
                'online' => $item['online']
            );

            array_push($result_tmp, $rs); // append recordset
        }

        if (count($result_tmp) > 0) {
            $result = array(); // result array

            foreach ($result_tmp as $rs) { // run results
                $children = $this->fetchTree($rs['idpica_alloc'], ($level + 1), $useTreeStatus);
                if ($children !== false && $rs['status'] == 'expanded') {
                    $rs['children'] = $children;
                }
                array_push($result, $rs);
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Fetch ContentAllocation tree
     * Consider offline/online status
     *
     * @created 21.11.2005 Willi Man
     *
     * @param mixed $parentId
     * @param int $level
     * @return array with ContentAllocation id's
     */
    public function fetchTreeIds($parentId = false, $level = 0, $showOffline = false) {

        // fetch current lang category
        $sql = "SELECT
                    tree.idpica_alloc, tree.parentid, tree.sortorder
                FROM
                    " . $this->_table['pica_alloc'] . " as tree";

        if ($parentId === false) { // fetch from root node
            $sql .= " WHERE tree.parentid IS NULL";
        } else { // fetch by given id
            $sql .= " WHERE tree.parentid = " . cSecurity::toInteger($parentId);
        }

        $sql .= " ORDER BY sortorder ASC";

        if ($this->_debug) {
            print "<!-- "; print $sql; print " -->";
        }

        $this->_db->query($sql);

        $result_tmp = array(); // tmp result array
        while ($this->_db->nextRecord()) { // walk resultset

            $item = $this->fetchItemNameLang($this->_db->f('idpica_alloc'));

            if ($showOffline || $item['online'] == 1) {
                $rs = array(
                    'idpica_alloc' => $this->_db->f('idpica_alloc')
                );

                array_push($result_tmp, $rs); // append recordset
            }
        }

        if (count($result_tmp) > 0) {
            $result = array(); // result array
            foreach ($result_tmp as $rs) { // run results
                $children = $this->fetchTreeIds($rs['idpica_alloc'], $level + 1, $showOffline);
                if ($children !== false) {
                    $rs['children'] = $children;
                }
                array_push($result, $rs);
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Set tree status
     *
     * @param integer $idpica_alloc
     */
    public function setTreeStatus($idpica_alloc) {
    	$idpica_alloc = cSecurity::toInteger($idpica_alloc);
        if (is_array($this->_treeStatus) && array_key_exists($idpica_alloc, $this->_treeStatus)) { // expand
            unset($this->_treeStatus[$idpica_alloc]);
        } else { // collapse
            $this->_treeStatus[$idpica_alloc] = true;
        }
        $this->_user->setProperty("expandstate", $this->_uuid, serialize($this->_treeStatus));
    }

    /**
     * Load tree status
     *
     */
    public function loadTreeStatus() {
        $status = $this->_user->getProperty("expandstate", $this->_uuid);
        if ($status !== false) {
            $this->_treeStatus = unserialize($status);
        }
    }

    /**
     * Fetch parent via idpica_alloc variable
     *
     * @param integer$idpica_alloc
     * @return array|bool
     */
    public function fetchParent($idpica_alloc) {
        $sql = "SELECT idpica_alloc FROM ".$this->_table['pica_alloc']." WHERE parentId = " . cSecurity::toInteger($idpica_alloc);
        $this->_db->query($sql);

        if ($this->_db->nextRecord()) {
            return $this->fetchItem($this->_db->f('idpica_alloc'));
        } else {
            return false;
        }
    }

    /**
     * Old unused function
     */
    public function fetchParents () {}

    /**
     * Fetch level
     *
     * @param bool $parentId
     * @param bool $showOffline
     * @return array
     */
    protected function fetchLevel($parentId = false, $showOffline = false) {
        // fetch current lang category
        $sql = "SELECT
                    tree.idpica_alloc, tree.parentid, tree.sortorder
                FROM
                    " . $this->_table['pica_alloc'] . " as tree
                LEFT JOIN ".$this->_table['pica_lang']." as treelang USING (idpica_alloc)";

        if ($parentId === false) { // fetch from root node
            $sql .= " WHERE tree.parentid IS NULL";
        } else { // fetch by given id
            $sql .= " WHERE tree.parentid = " . cSecurity::toInteger($parentId);
        }

        if ($showOffline === false) {
            $sql .= " AND treelang.online = 1";
        }

        $sql .= " ORDER BY sortorder ASC";

        $this->_db->query($sql);

        $result_tmp = array(); // tmp result array
        while ($this->_db->nextRecord()) { // walk resultset
            $item = $this->
            fetchItemNameLang($this->_db->f('idpica_alloc'));

            $itemStatus = 'expanded';
            if (is_array($this->_treeStatus) && array_key_exists($this->_db->f('idpica_alloc'), $this->_treeStatus)) {
                $itemStatus = 'collapsed';
            }

            $rs = array(
                'idpica_alloc' => $this->_db->f('idpica_alloc'),
                'parentid' => ($this->_db->f('parentid') == NULL) ? false : $this->_db->f('parentid'),
                'sortorder' => $this->_db->f('sortorder'),
                'name' => $this->_outFilter($item['name']),
                'idlang' => $item['idlang'],
                'level' => 0,
                'status' => $itemStatus,
                'online' => $item['online']
            );

            array_push($result_tmp, $rs); // append recordset
        }

        return $result_tmp;
    }

    /**
     * Store item into database
     *
     * @param $treeItem
     * @return mixed
     */
    public function storeItem($treeItem) {

        if (!$treeItem['idpica_alloc']) { // insert
            //$treeItem['idpica_alloc'] = $this->db->nextid($this->table['pica_alloc']);
            $treeItem['sortorder'] = $this->_fetchMaxOrder($treeItem['parentid']) + 1;

            if ($treeItem['parentid'] == 'root') {
                $treeItem['parentid'] = 'NULL';
            }

            $treeItem['name'] = $this->_inFilter($treeItem['name']);

            $sql = "INSERT INTO " . $this->_table['pica_alloc'] . "
                    (parentid, sortorder)
                    VALUES
                    (" . cSecurity::toInteger($treeItem['parentid']) . ", " . cSecurity::toInteger($treeItem['sortorder']) . ")";
            $this->_db->query($sql);
            $treeItem['idpica_alloc'] = $this->_db->getLastInsertedId();
            $sql = "INSERT INTO " . $this->_table['pica_lang'] . "
                    (idpica_alloc, idlang, name)
                    VALUES
                    (" . cSecurity::toInteger($treeItem['idpica_alloc']) . ", " . cSecurity::toInteger($this->_lang) . ", '" . $this->_db->escape($treeItem['name']) . "')";
            $this->_db->query($sql);

        } else { // update
            $treeItem['name'] = $this->_inFilter($treeItem['name']);

            $sql = "SELECT * FROM " . $this->_table['pica_lang'] . " WHERE idpica_alloc = " . cSecurity::toInteger($treeItem['idpica_alloc']) . " AND idlang = " . cSecurity::toInteger($this->_lang);
            $this->_db->query($sql);

            if ($this->_db->numRows() > 0) {
                #Update existing translation
                $sql = "UPDATE " . $this->_table['pica_lang'] . " SET name = '" . $this->_db->escape($treeItem['name']) . "' WHERE idpica_alloc = " . cSecurity::toInteger($treeItem['idpica_alloc']) . "
                        AND idlang = " . cSecurity::toInteger($this->_lang);
            } else {
                #Get current online status for item
                $sql = "SELECT * FROM " . $this->_table['pica_lang'] . " WHERE idpica_alloc = " . $treeItem['idpica_alloc'] . " ORDER BY idlang";
                $this->_db->query($sql);

                if ($this->_db->nextRecord()) {
                    $online_status = $this->_db->f('online');
                } else {
                    $online_status = 0;
                }

                #Insert new translation
                $sql = "INSERT INTO " . $this->_table['pica_lang'] . "(idpica_alloc, idlang, name, online) VALUES (".cSecurity::toInteger($treeItem['idpica_alloc']).", ".cSecurity::toInteger($this->_lang).",
                        '".$this->_db->escape($treeItem['name'])."', ".cSecurity::toInteger($online_status).")";
            }

            $this->_db->query($sql);
        }

        return $treeItem;
    }

    /**
     * Set status to online
     *
     * @param integer $idpica_alloc
     */
    public function setOnline($idpica_alloc) {
        $this->_switchOnOffline($idpica_alloc, 1);
    }

    /**
     * Set status to offline
     *
     * @param integer$idpica_alloc
     */
    public function setOffline($idpica_alloc) {
        $this->_switchOnOffline($idpica_alloc, 0);
    }

    /**
     * Set status to online or offline
     *
     * @param integer $idpica_alloc
     * @param integer $status
     */
    protected function _switchOnOffline($idpica_alloc, $status) {
        $sql = "UPDATE " . $this->_table['pica_lang'] . " SET online = " . cSecurity::toInteger($status) . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc) . "
                AND idlang = " . cSecurity::toInteger($this->_lang);
        $this->_db->query($sql);
    }

    /**
     * Move item up
     *
     * @param integer $idpica_alloc
     */
    public function itemMoveUp($idpica_alloc) {
        $treeItem = $this->fetchItem($idpica_alloc);
        $treeItem_old = $treeItem;
        $treeItem['sortorder']--;

        if ($treeItem['sortorder'] < $treeItem_old['sortorder']) {
            if ($treeItem['sortorder'] >= 1) {
                $this->_decreaseOrder($treeItem['parentid'], $treeItem_old['sortorder']);
                $this->_increaseOrder($treeItem['parentid'], $treeItem['sortorder']);
            } else {
                $treeItem['sortorder'] = $treeItem_old['sortorder'];
            }
        }

        $sql = "UPDATE " . $this->_table['pica_alloc'] . " SET sortorder = " . $treeItem['sortorder'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc);
        $this->_db->query($sql);
    }

    /**
     * Old unused function
     */
    public function itemMoveDown() {}

    /**
     * Delete content allocation item
     *
     * @param integer $idpica_alloc
     * @return boolean
     */
    public function deleteItem($idpica_alloc) {
        $sql = "DELETE FROM " . $this->_table['pica_alloc'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc);
        $this->_db->query($sql);

        $sql = "DELETE FROM " . $this->_table['pica_lang'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc);
        $this->_db->query($sql);

        $sql = "DELETE FROM " . $this->_table['pica_alloc_con'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc);
        $this->_db->query($sql);

        return true;
    }

    /**
     * Get parentid and sortorder
     *
     * @param integer $idpica_alloc
     * @return array|bool
     */
    function fetchItem($idpica_alloc) {
        $sql = "SELECT parentid, sortorder FROM " . $this->_table['pica_alloc'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc);
        $this->_db->query($sql);

        $item = $this->fetchItemNameLang($idpica_alloc);

        if ($this->_db->nextRecord()) {
            $row = array(
                'idpica_alloc' => $idpica_alloc,
                'parentid' => ($this->_db->f('parentid') == NULL) ? false : $this->_db->f('parentid'),
                'sortorder' => $this->_db->f('sortorder'),
                'name' => $item['name'],
                'idlang' => $item['idlang'],
                'online' => $item['online']
            );
            return $row;
        } else {
            return false;
        }
    }

    /**
     * Get name, id of language and online/offline status
     *
     * @param integer $idpica_alloc
     * @return array|bool
     */
    public function fetchItemNameLang($idpica_alloc) {

        // temporary new db instance
        $db = cRegistry::getDb();

        $sql = "SELECT name, idlang, online FROM " . $this->_table['pica_lang'] . " WHERE idpica_alloc = " . cSecurity::toInteger($idpica_alloc) . " AND idlang = " . cSecurity::toInteger($this->_lang);
        $db->query($sql);

        $result = array();
        if ($db->nextRecord()) { // item found for this language

            $result['name']   = $this->_outFilter($db->f('name'));
            $result['idlang'] = $db->f('idlang');
            $result['online'] = $db->f('online');

        } else { // no item in this language found
			return false;
        }

        return $result;
    }

    /**
     * Get maximal sortorder
     *
     * @param boolean $parentId
     * @return integer
     */
    protected function _fetchMaxOrder($parentId = false) {

        if ($parentId == 'root') {
            $parentId = false;
        }

        $sql = "SELECT MAX(sortorder) as max FROM " . $this->_table['pica_alloc'];
        if ($parentId === false) {
            $sql .= " WHERE parentid = 0";
        } else {
            $sql .= " WHERE parentid = " . cSecurity::toInteger($parentId);
        }
        $this->_db->query($sql);
        if ($this->_db->nextRecord()) {
            return $this->_db->f('max');
        } else {
            return 0;
        }
    }

    /**
     * Decrease order (value at database - 1)
     *
     * @param bool $parentId
     * @param integer $fromOrder
     */
    protected function _decreaseOrder($parentId = false, $fromOrder) {
        $sql = "UPDATE " . $this->_table['pica_alloc'] . " SET sortorder = sortorder - 1 WHERE sortorder >= " . cSecurity::toInteger($fromOrder);
        if ($parentId === false) {
            $sql .= " AND parentid IS NULL";
        } else {
            $sql .= " AND parentid = " . cSecurity::toInteger($parentId);
        }
        $this->_db->query($sql);
    }

    /**
     * Increase order (value at database + 1)
     *
     * @param bool $parentId
     * @param $fromOrder
     */
    protected function _increaseOrder($parentId = false, $fromOrder) {
        $sql = "UPDATE " . $this->_table['pica_alloc'] . " SET sortorder = sortorder + 1 WHERE sortorder >= " . cSecurity::toInteger($fromOrder);
        if ($parentId === false) {
            $sql .= " AND parentid IS NULL";
        } else {
            $sql .= " AND parentid = " . cSecurity::toInteger($parentId);
        }
        $this->_db->query($sql);
    }

    /**
     * Set method for filters
     *
     * @param array $arrInFilters
     * @param array $arrOutFilters
     */
    public function setFilters($arrInFilters = array(), $arrOutFilters = array()) {
        $this->_arrInFilters = $arrInFilters;
        $this->_arrOutFilters = $arrOutFilters;
    }

    /**
     * Get data from filters
     *
     * @param $data
     * @return mixed
     */
    protected function _inFilter($data) {

        foreach ($this->_arrInFilters as $_function) {
            if (function_exists($_function)) {
                $data = $_function($data);
            }
        }

        return $data;
    }

    /**
     * Get data from filters
     *
     * @param $data
     * @return mixed
     */
    protected function _outFilter($data) {

        foreach ($this->_arrOutFilters as $_function) {
            if (function_exists($_function)) {
                $data = $_function($data);
            }
        }

        return $data;
    }
}
?>