<?php

/**
 * This file contains the tree class for the plugin content allocation.
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
 * Tree class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiTree
{

    /**
     *
     * CONTENIDO Database
     * @var object cDb
     */
    protected $_db = null;

    /*
     *
     * @var bool
     */
    protected $_debug = false;

    /**
     *
     * @var array
     */
    protected $_table = [];

    /**
     *
     * @var int
     */
    protected $_lang = 1;

    /**
     *
     * @var int
     */
    protected $_client = 1;

    /**
     *
     * @var int
     */
    protected $_defaultLang = 1;

    /**
     *
     * @var bool
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
    protected $_treeStatus = [];

    /**
     *
     * @var string
     */
    protected $_uuid = '';

    /**
     *
     * @var array
     */
    protected $_arrInFilters = ['htmlspecialchars', 'addslashes'];

    /**
     *
     * @var array
     */
    protected $_arrOutFilters = ['stripslashes', 'htmldecode'];

    /**
     * pApiTree constructor
     *
     * @param string $uuid
     *
     * @throws cDbException|cException
     */
    public function __construct($uuid)
    {
        $cfg = cRegistry::getConfig();
        $auth = cRegistry::getAuth();

        $this->_db = cRegistry::getDb();
        $this->_table = $cfg['tab'];
        $this->_lang = cRegistry::getLanguageId();
        $this->_client = cRegistry::getClientId();

        $this->_uuid = $uuid;

        $this->_user = new cApiUser($auth->auth['uid']);
        $this->loadTreeStatus();
    }

    /**
     * Fetch tree
     *
     * @param mixed $parentId
     * @param int $level
     * @param bool $useTreeStatus (if true use expand/collapsed status of the tree, otherwise not)
     *
     * @return array|bool
     * @throws cDbException
     */
    public function fetchTree($parentId = false, $level = 0, bool $useTreeStatus = true)
    {
        $parentId = $parentId === false ? 0 : cSecurity::toInteger($parentId);

        // fetch current lang category
        $sql = "SELECT * FROM `%s` WHERE `parentid` = %d ORDER BY `sortorder` ASC";
        $this->_db->query($sql, $this->_table['pica_alloc'], $parentId);

        $result_tmp = []; // tmp result array
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

            $rs = [
                'idpica_alloc' => $this->_db->f('idpica_alloc'),
                'parentid' => ($this->_db->f('parentid') == NULL) ? false : $this->_db->f('parentid'),
                'sortorder' => $this->_db->f('sortorder'),
                'name' => $this->_outFilter($item['name']),
                'idlang' => $item['idlang'],
                'level' => $level,
                'status' => $itemStatus,
                'online' => $item['online'],
                'children' => [],
            ];

            $result_tmp[] = $rs; // append recordset
        }

        if (count($result_tmp) > 0) {
            $result = []; // result array

            foreach ($result_tmp as $rs) { // run results
                $children = $this->fetchTree($rs['idpica_alloc'], ($level + 1), $useTreeStatus);
                if ($children !== false && $rs['status'] == 'expanded') {
                    $rs['children'] = $children;
                }
                $result[] = $rs;
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
     * @param bool $showOffline
     *
     * @return bool|array with ContentAllocation id's
     * @throws cDbException
     */
    public function fetchTreeIds($parentId = false, $level = 0, bool $showOffline = false)
    {
        $parentIdSql = $parentId === false ? 'IS NULL' : '= ' . cSecurity::toInteger($parentId);

        // fetch current lang category
        $sql = "SELECT * FROM `%s` WHERE `parentid` %s ORDER BY `sortorder` ASC";
        $this->_db->query($sql, $this->_table['pica_alloc'], $parentIdSql);

        if ($this->_debug) {
            print "<!-- ";
            print $sql;
            print " -->";
        }

        $this->_db->query($sql);

        $result_tmp = []; // tmp result array
        while ($this->_db->nextRecord()) { // walk resultset
            $item = $this->fetchItemNameLang($this->_db->f('idpica_alloc'));

            if ($showOffline || $item['online'] == 1) {
                $result_tmp[] = [
                    'idpica_alloc' => $this->_db->f('idpica_alloc')
                ];
            }
        }

        if (count($result_tmp) > 0) {
            $result = []; // result array
            foreach ($result_tmp as $rs) { // run results
                $children = $this->fetchTreeIds($rs['idpica_alloc'], $level + 1, $showOffline);
                if ($children !== false) {
                    $rs['children'] = $children;
                }
                $result[] = $rs;
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Set tree status
     *
     * @param int $idpica_alloc
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setTreeStatus($idpica_alloc)
    {
        $idpica_alloc = cSecurity::toInteger($idpica_alloc);
        if (is_array($this->_treeStatus) && array_key_exists($idpica_alloc, $this->_treeStatus)) { // expand
            unset($this->_treeStatus[$idpica_alloc]);
        } else { // collapse
            $this->_treeStatus[$idpica_alloc] = true;
        }
        $this->_user->setProperty('expandstate', $this->_uuid, serialize($this->_treeStatus));
    }

    /**
     * Load tree status
     *
     * @throws cDbException|cException
     */
    public function loadTreeStatus()
    {
        $status = $this->_user->getProperty('expandstate', $this->_uuid);
        if ($status !== false) {
            $this->_treeStatus = unserialize($status);
        }
    }

    /**
     * Fetch parent via idpica_alloc variable
     *
     * @param int $idpica_alloc
     *
     * @return array|bool
     * @throws cDbException
     */
    public function fetchParent($idpica_alloc)
    {
        $sql = "SELECT `idpica_alloc` FROM `%s` WHERE `parentId` = %d";
        $this->_db->query($sql, $this->_table['pica_alloc'], $idpica_alloc);

        if ($this->_db->nextRecord()) {
            return $this->fetchItem($this->_db->f('idpica_alloc'));
        } else {
            return false;
        }
    }

    /**
     * Old unused function
     */
    public function fetchParents()
    {
    }

    /**
     * Fetch level
     *
     * @param bool $parentId
     * @param bool $showOffline
     *
     * @return array
     * @throws cDbException
     */
    protected function fetchLevel($parentId = false, $showOffline = false)
    {
        // fetch current lang category
        $sql = "SELECT
                    tree.idpica_alloc, tree.parentid, tree.sortorder
                FROM
                    " . $this->_table['pica_alloc'] . " AS tree
                LEFT JOIN " . $this->_table['pica_lang'] . " AS treelang USING (idpica_alloc)";

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

        $result_tmp = []; // tmp result array
        while ($this->_db->nextRecord()) { // walk resultset
            $item = $this->
            fetchItemNameLang($this->_db->f('idpica_alloc'));

            $itemStatus = 'expanded';
            if (is_array($this->_treeStatus) && array_key_exists($this->_db->f('idpica_alloc'), $this->_treeStatus)) {
                $itemStatus = 'collapsed';
            }

            $result_tmp[] = [
                'idpica_alloc' => $this->_db->f('idpica_alloc'),
                'parentid' => ($this->_db->f('parentid') == NULL) ? false : $this->_db->f('parentid'),
                'sortorder' => $this->_db->f('sortorder'),
                'name' => $this->_outFilter($item['name']),
                'idlang' => $item['idlang'],
                'level' => 0,
                'status' => $itemStatus,
                'online' => $item['online']
            ];
        }

        return $result_tmp;
    }

    /**
     * Store item into database
     *
     * @param $treeItem
     *
     * @return mixed
     * @throws cDbException
     */
    public function storeItem($treeItem)
    {
        $treeItem['idpica_alloc'] = cSecurity::toInteger($treeItem['idpica_alloc'] ?? '0');
        $treeItem['parentid'] = cSecurity::toInteger($treeItem['parentid'] ?? '0');
        $treeItem['name'] = $treeItem['name'] ?? '';

        if (!$treeItem['idpica_alloc']) { // insert
            $treeItem['sortorder'] = $this->_fetchMaxOrder($treeItem['parentid']) + 1;

            if ($treeItem['parentid'] <= 0 || $treeItem['parentid'] == 'root') {
                $treeItem['parentid'] = 'NULL';
            }

            $treeItem['name'] = $this->_inFilter($treeItem['name']);

            $sql = $this->_db->buildInsert($this->_table['pica_alloc'], [
                'parentid' => $treeItem['parentid'],
                'sortorder' => $treeItem['sortorder'],
            ]);
            $this->_db->query($sql);

            $treeItem['idpica_alloc'] = cSecurity::toInteger($this->_db->getLastInsertedId());
            $sql = $this->_db->buildInsert($this->_table['pica_lang'], [
                'idpica_alloc' => $treeItem['idpica_alloc'],
                'idlang' => $this->_lang,
                'name' => $treeItem['name'],
            ]);
            $this->_db->query($sql);

        } else { // update
            $treeItem['name'] = $this->_inFilter($treeItem['name']);

            $sql = "SELECT `idpica_alloc` FROM `%s` WHERE `idpica_alloc` = %d AND `idlang` = %d";
            $this->_db->query($sql, $this->_table['pica_lang'], $treeItem['idpica_alloc'], $this->_lang);

            if ($this->_db->numRows() > 0) {
                // Update existing translation
                $sql = "UPDATE `%s` SET `name` = '%s' WHERE `idpica_alloc` = %d AND `idlang` = %d";
                $this->_db->query($sql, $this->_table['pica_lang'], $treeItem['name'], $treeItem['idpica_alloc'], $this->_lang);
            } else {
                // Get current online status for item
                $sql = "SELECT `online` FROM `%s` WHERE `idpica_alloc` = %d ORDER BY `idlang`";
                $this->_db->query($sql, $this->_table['pica_lang'], $treeItem['idpica_alloc']);

                if ($this->_db->nextRecord()) {
                    $online_status = cSecurity::toInteger($this->_db->f('online'));
                } else {
                    $online_status = 0;
                }

                // Insert new translation
                $sql = $this->_db->buildInsert($this->_table['pica_lang'], [
                    'idpica_alloc' => $treeItem['idpica_alloc'],
                    'idlang' => $this->_lang,
                    'name' => $treeItem['name'],
                    'online' => $online_status
                ]);
                $this->_db->query($sql);
            }
        }

        return $treeItem;
    }

    /**
     * Set status to online
     *
     * @param int $idpica_alloc
     *
     * @throws cDbException
     */
    public function setOnline($idpica_alloc)
    {
        $this->_switchOnOffline($idpica_alloc, 1);
    }

    /**
     * Set status to offline
     *
     * @param int $idpica_alloc
     *
     * @throws cDbException
     */
    public function setOffline($idpica_alloc)
    {
        $this->_switchOnOffline($idpica_alloc, 0);
    }

    /**
     * Set status to online or offline
     *
     * @param int $idpica_alloc
     * @param int $status
     *
     * @throws cDbException
     */
    protected function _switchOnOffline($idpica_alloc, $status)
    {
        $sql = "UPDATE `%s` SET `online` = %d WHERE `idpica_alloc` = %d AND `idlang` = %d";
        $this->_db->query($sql, $this->_table['pica_lang'], $status, $idpica_alloc, $this->_lang);
    }

    /**
     * Move item up
     *
     * @param int $idpica_alloc
     *
     * @throws cDbException
     */
    public function itemMoveUp($idpica_alloc)
    {
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

        $sql = "UPDATE `%s` SET `sortorder` = %d WHERE `idpica_alloc` = %d";
        $this->_db->query($sql, $this->_table['pica_alloc'], $treeItem['sortorder'], $idpica_alloc);
    }

    /**
     * Old unused function
     */
    public function itemMoveDown()
    {
    }

    /**
     * Delete content allocation item
     *
     * @param int $idpica_alloc
     *
     * @return bool
     * @throws cDbException
     */
    public function deleteItem($idpica_alloc): bool
    {
        $sql = "DELETE FROM `%s` WHERE `idpica_alloc` = %d";

        $this->_db->query($sql, $this->_table['pica_alloc'], $idpica_alloc);
        $this->_db->query($sql, $this->_table['pica_lang'], $idpica_alloc);
        $this->_db->query($sql, $this->_table['pica_alloc_con'], $idpica_alloc);

        return true;
    }

    /**
     * Get parentid and sortorder
     *
     * @param int $idpica_alloc
     *
     * @return array|bool
     * @throws cDbException
     */
    function fetchItem($idpica_alloc)
    {
        $sql = "SELECT `parentid`, `sortorder` FROM `%s` WHERE `idpica_alloc` = %d";
        $this->_db->query($sql, $this->_table['pica_alloc'], $idpica_alloc);

        $item = $this->fetchItemNameLang($idpica_alloc);

        if ($this->_db->nextRecord()) {
            return [
                'idpica_alloc' => $idpica_alloc,
                'parentid' => ($this->_db->f('parentid') == NULL) ? false : $this->_db->f('parentid'),
                'sortorder' => $this->_db->f('sortorder'),
                'name' => $item['name'],
                'idlang' => $item['idlang'],
                'online' => $item['online']
            ];
        } else {
            return false;
        }
    }

    /**
     * Get name, id of language and online/offline status
     *
     * @param int $idpica_alloc
     *
     * @return array|bool
     * @throws cDbException
     */
    public function fetchItemNameLang($idpica_alloc)
    {
        // temporary new db instance
        $db = cRegistry::getDb();

        $sql = "SELECT `name`, `idlang`, `online` FROM `%s` WHERE `idpica_alloc` = %d AND `idlang` = %d";
        $db->query($sql, $this->_table['pica_lang'], $idpica_alloc, $this->_lang);

        $result = [];
        if ($db->nextRecord()) {
            $result['name'] = $this->_outFilter($db->f('name'));
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
     * @param bool|int $parentId
     *
     * @return int
     * @throws cDbException
     */
    protected function _fetchMaxOrder($parentId = false): int
    {
        if ($parentId == 'root') {
            $parentId = false;
        }
        $parentId = $parentId === false ? 0 : $parentId;

        $sql = "SELECT MAX(sortorder) AS max FROM `%s` WHERE `parentid` = %d";
        $this->_db->query($sql, $this->_table['pica_alloc'], $parentId);
        if ($this->_db->nextRecord()) {
            return cSecurity::toInteger($this->_db->f('max'));
        } else {
            return 0;
        }
    }

    /**
     * Decrease order (value at database - 1)
     *
     * @param bool $parentId
     * @param int $fromOrder
     *
     * @throws cDbException
     */
    protected function _decreaseOrder($parentId = false, $fromOrder = 1)
    {
        $parentIdSql = $parentId === false ? 'IS NULL' : '= ' . cSecurity::toInteger($parentId);
        $sql = "UPDATE `%s` SET `sortorder` = `sortorder` - 1 WHERE `sortorder` >= %d AND `parentid` %s";
        $this->_db->query($sql, $this->_table['pica_alloc'], $fromOrder, $parentIdSql);
    }

    /**
     * Increase order (value at database + 1)
     *
     * @param bool $parentId
     * @param int $fromOrder
     *
     * @throws cDbException
     */
    protected function _increaseOrder($parentId = false, $fromOrder = 1)
    {
        $parentIdSql = $parentId === false ? 'IS NULL' : '= ' . cSecurity::toInteger($parentId);
        $sql = "UPDATE `%s` SET `sortorder` = `sortorder` + 1 WHERE `sortorder` >= %d AND `parentid` %s";
        $this->_db->query($sql, $this->_table['pica_alloc'], $fromOrder, $parentIdSql);
    }

    /**
     * Set method for filters
     *
     * @param array $arrInFilters
     * @param array $arrOutFilters
     */
    public function setFilters(array $arrInFilters = [], array $arrOutFilters = [])
    {
        $this->_arrInFilters = $arrInFilters;
        $this->_arrOutFilters = $arrOutFilters;
    }

    /**
     * Get data from filters
     *
     * @param $data
     * @return mixed
     */
    protected function _inFilter($data)
    {
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
    protected function _outFilter($data)
    {
        foreach ($this->_arrOutFilters as $_function) {
            if (function_exists($_function)) {
                $data = $_function($data);
            }
        }

        return $data;
    }

}
