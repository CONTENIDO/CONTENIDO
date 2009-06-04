<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Stores and handles content allocation management
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend plugins
 * @version    0.10.1
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
 
/**
 * Todo
 * addslashes to all string db inserts
 * check if default language exists if new entry is added (otherwise)
 */
class pApiTree {

	/**
	 * 
	 */
	var $db = null;

	/**
	 * 
	 */
	var $table = null;

	/**
	 * 
	 */
	var $lang = 1;

	/**
	 * 
	 */
	var $client = 1;

	/**
	 * 
	 */
	var $defaultLang = 1;

	/**
	 * 
	 */
	var $logger = null;

	/**
	 * 
	 */
	var $user = null;

	/**
	 * 
	 */
	var $treeStatus = array();

	/**
	 * 
	 */
	var $uuid = null;

	/**
	 * 
	 */
	var $_arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

	/**
	 * 
	 */    
    var $_arrOutFilters = array('stripslashes', 'htmldecode', 'urldecode');

	function pApiTree ($uuid) {
		global $db, $cfg, $lang, $client, $auth;

		$this->db = new DB_Contenido;
		$this->table = $cfg['tab'];
		$this->lang = $lang;
		$this->client = $client;
		$this->bDebug = false;

		$this->uuid = $uuid;

		$this->user =  new cApiUser($auth->auth["uid"]);
		$this->loadTreeStatus();
	}
	
	/**
	 * 
	 * @param mixed $parentId
	 * @param int $level
	 * @param boolean $bUseTreeStatus (if true use expand/collapsed status of the tree, otherwise not)
	 * @modified 27.10.2005 Willi Man
	 */
	function fetchTree ($parentId = false, $level = 0, $bUseTreeStatus = true) {
	
		// fetch current lang category
		$sql = "SELECT
					tree.idpica_alloc, tree.parentid, tree.sortorder
				FROM
					" . $this->table['pica_alloc'] . " as tree";
		
		if ($parentId === false) { // fetch from root node
			$sql .= " WHERE tree.parentid = '0'";
		} else { // fetch by given id
			$sql .= " WHERE tree.parentid = " . Contenido_Security::toInteger($parentId);
		}

		$sql .= " ORDER BY sortorder ASC";

		$this->db->query($sql);
		
		$result_tmp = array(); // tmp result array
		while ($this->db->next_record()) { // walk resultset
			$item = $this->_fetchItemNameLang($this->db->f('idpica_alloc'));
			
			$itemStatus = 'expanded';
			
			if ($bUseTreeStatus) # modified 27.10.2005
			{
				if (is_array($this->treeStatus) && array_key_exists($this->db->f('idpica_alloc'), $this->treeStatus)) 
				{
					$itemStatus = 'collapsed';
				}
			}
			
			$rs = array (
				'idpica_alloc' => $this->db->f('idpica_alloc'),
				'parentid' => ($this->db->f('parentid') == NULL) ? false : $this->db->f('parentid'),
				'sortorder' => $this->db->f('sortorder'),
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
				$children = $this->fetchTree($rs['idpica_alloc'], $level + 1, $bUseTreeStatus);
				if ($children !== false && $rs['status'] == 'expanded') {
					$rs['children'] = $children;	
				}
				array_push($result, $rs);
			}
			return $result;
		} else
		{
			return false;
		}
	}
	
	/**
	 * Fetch content allocation tree
	 * Consider offline/online status
	 * 
	 * @created 21.11.2005 Willi Man
	 * 
	 * @param mixed $parentId
	 * @param int $level
	 * @return array with content allocation id's
	 */
	function fetchTreeIds ($parentId = false, $level = 0, $showOffline = false) {
	
		// fetch current lang category
		$sql = "SELECT
					tree.idpica_alloc, tree.parentid, tree.sortorder
				FROM
					" . $this->table['pica_alloc'] . " as tree";
		
		if ($parentId === false) { // fetch from root node
			$sql .= " WHERE tree.parentid IS NULL";
		} else { // fetch by given id
			$sql .= " WHERE tree.parentid = " . Contenido_Security::toInteger($parentId);
		}

		$sql .= " ORDER BY sortorder ASC";

		if ($this->bDebug) {print "<!-- "; print $sql; print " -->";}

		$this->db->query($sql);
		
		$result_tmp = array(); // tmp result array
		while ($this->db->next_record()) { // walk resultset
			
			$item = $this->_fetchItemNameLang($this->db->f('idpica_alloc'));
			
			if ($this->bDebug) {print "<!-- "; print_r($item); print " -->";}
			
			if ($showOffline OR $item['online'] == 1)
			{
				$rs = array (
					'idpica_alloc' => $this->db->f('idpica_alloc')
				);
				
				array_push($result_tmp, $rs); // append recordset
			}
		}
		
		if (count($result_tmp) > 0) {
			$result = array(); // result array
			foreach ($result_tmp as $rs) { // run results
				$children = $this->fetchTreeIds($rs['idpica_alloc'], $level + 1, $bUseTreeStatus);
				if ($children !== false) {
					$rs['children'] = $children;	
				}
				array_push($result, $rs);
			}
			return $result;
		} else
		{
			return false;
		}
	}

	function setTreeStatus($idpica_alloc) {
		if (is_array($this->treeStatus) && array_key_exists($idpica_alloc, $this->treeStatus)) { // expand
			unset($this->treeStatus[$idpica_alloc]);
		} else { // collapse
			$this->treeStatus[$idpica_alloc] = true;	
		}
		$this->user->setProperty("expandstate", $this->_uuid, serialize($this->treeStatus));
	}
	
	function loadTreeStatus () {
		$status = $this->user->getProperty("expandstate", $this->_uuid);
		if ($status !== false) {
			$this->treeStatus = unserialize($status);
		}
	}
	
	function fetchParent ($idpica_alloc) {
		$sql = "SELECT idpica_alloc FROM ".$this->table['pica_alloc']." WHERE parentId = " . Contenido_Security::toInteger($idpica_alloc);
		$this->db->query($sql);
		
		if ($this->db->next_record()) {
			return $this->fetchItem($this->db->f('idpica_alloc'));
		} else {
			return false;	
		}
	}
	
	function fetchParents () {}

	function fetchLevel ($parentId = false, $showOffline = false) {
		// fetch current lang category
		$sql = "SELECT
					tree.idpica_alloc, tree.parentid, tree.sortorder
				FROM
					" . $this->table['pica_alloc'] . " as tree
				LEFT JOIN ".$this->table['pica_lang']." as treelang USING (idpica_alloc)";
		
		if ($parentId === false) { // fetch from root node
			$sql .= " WHERE tree.parentid IS NULL";
		} else { // fetch by given id
			$sql .= " WHERE tree.parentid = " . Contenido_Security::toInteger($parentId);
		}

		if ($showOffline === false) {
			$sql .= " AND treelang.online = 1";
		}

		$sql .= " ORDER BY sortorder ASC";

		$this->db->query($sql);

		$result_tmp = array(); // tmp result array
		while ($this->db->next_record()) { // walk resultset
			$item = $this->_fetchItemNameLang($this->db->f('idpica_alloc'));
			
			$itemStatus = 'expanded';
			if (is_array($this->treeStatus) && array_key_exists($this->db->f('idpica_alloc'), $this->treeStatus)) {
				$itemStatus = 'collapsed';
			}
			
			$rs = array (
				'idpica_alloc' => $this->db->f('idpica_alloc'),
				'parentid' => ($this->db->f('parentid') == NULL) ? false : $this->db->f('parentid'),
				'sortorder' => $this->db->f('sortorder'),
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
	
	function storeItem ($treeItem) {
		
		if (!$treeItem['idpica_alloc']) { // insert
			$treeItem['idpica_alloc'] = $this->db->nextid($this->table['pica_alloc']);
			$treeItem['sortorder'] = $this->_fetchMaxOrder($treeItem['parentid']) + 1;
			
			if ($treeItem['parentid'] == 'root') {
				$treeItem['parentid'] = 'NULL';
			}
			
			$treeItem['name'] = $this->_inFilter($treeItem['name']);
			
			$sql = "INSERT INTO " . $this->table['pica_alloc'] . "
					(idpica_alloc, parentid, sortorder)
					VALUES
					(" . Contenido_Security::toInteger($treeItem['idpica_alloc']) . ", " . Contenido_Security::toInteger($treeItem['parentid']) . ", " . Contenido_Security::toInteger($treeItem['sortorder']) . ")";
			$this->db->query($sql);

			$sql = "INSERT INTO " . $this->table['pica_lang'] . "
					(idpica_alloc, idlang, name)
					VALUES
					(" . Contenido_Security::toInteger($treeItem['idpica_alloc']) . ", " . Contenido_Security::toInteger($this->lang) . ", '" . Contenido_Security::escapeDB($treeItem['name'], $this->db) . "')";
			$this->db->query($sql);

		} else { // update
			$treeItem['name'] = $this->_inFilter($treeItem['name']);
		
			$sql = "SELECT * FROM " . $this->table['pica_lang'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($treeItem['idpica_alloc']) . " AND idlang = " . Contenido_Security::toInteger($this->lang);
			$this->db->query($sql);

			if ($this->db->num_rows() > 0) {
				#Update existing translation
				$sql = "UPDATE " . $this->table['pica_lang'] . " SET name = '" . Contenido_Security::escapeDB($treeItem['name'], $this->db) . "' WHERE idpica_alloc = " . Contenido_Security::toInteger($treeItem['idpica_alloc']) . "
                        AND idlang = " . Contenido_Security::toInteger($this->lang);
			} else {
				#Get current online status for item
				$sql = "SELECT * FROM " . $this->table['pica_lang'] . " WHERE idpica_alloc = " . $treeItem['idpica_alloc'] . " ORDER BY idlang";
				$this->db->query($sql);
			
				if ($this->db->next_record()) {
					$online_status = $this->db->f('online');
				} else {
					$online_status = 0;
				}			
				
				#Insert new translation
				$sql = "INSERT INTO " . $this->table['pica_lang'] . "(idpica_alloc, idlang, name, online) VALUES ( ".Contenido_Security::toInteger($treeItem['idpica_alloc']).", ".Contenido_Security::toInteger($this->lang).",
                        '".Contenido_Security::escapeDB($treeItem['name'], $this->db)."', ".Contenido_Security::toInteger($online_status).")";
			}
			
			$this->db->query($sql);
		}
		
		return $treeItem;
	}
	
	function setOnline ($idpica_alloc) {
		$this->_switchOnOffline($idpica_alloc, 1);
	}
	
	function setOffline ($idpica_alloc) {
		$this->_switchOnOffline($idpica_alloc, 0);
	}
	
	function _switchOnOffline ($idpica_alloc, $status) {
		$sql = "UPDATE " . $this->table['pica_lang'] . " SET online = " . Contenido_Security::toInteger($status) . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc) . "
                AND idlang = " . Contenido_Security::toInteger($this->lang);
		$this->db->query($sql);
	}
	
	function itemMoveUp ($idpica_alloc) {
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

		$sql = "UPDATE " . $this->table['pica_alloc'] . " SET sortorder = " . $treeItem['sortorder'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc);
		$this->db->query($sql);
	}
	
	function itemMoveDown () {}
	
	function deleteItem ($idpica_alloc) {
		$sql = "DELETE FROM " . $this->table['pica_alloc'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc);
		$this->db->query($sql);
		
		$sql = "DELETE FROM " . $this->table['pica_lang'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc);
		$this->db->query($sql);
		
		$sql = "DELETE FROM " . $this->table['pica_alloc_con'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc);
		$this->db->query($sql);
	}
	
	function fetchItem ($idpica_alloc) {
		$sql = "SELECT parentid, sortorder FROM " . $this->table['pica_alloc'] . " WHERE idpica_alloc = " . $idpica_alloc;
		$this->db->query($sql);
		
		$item = $this->_fetchItemNameLang($idpica_alloc);
		
		if ($this->db->next_record()) {
			$row = array (
				'idpica_alloc' => $idpica_alloc,
				'parentid' => ($this->db->f('parentid') == NULL) ? false : $this->db->f('parentid'),
				'sortorder' => $this->db->f('sortorder'),
				'name' => $item['name'],
				'idlang' => $item['idlang'],
				'online' => $item['online']
			);
			return $row;
		} else {
			return false;
		}
	}
	
	function _fetchItemNameLang ($idpica_alloc) {
		$oDB = new DB_Contenido; // temp instance
		
		$sSQL = "SELECT name, idlang, online FROM " . $this->table['pica_lang'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc) . " AND idlang = " . Contenido_Security::toInteger($this->lang);
		$oDB->query($sSQL);
		
		$aResult = array();
		if ($oDB->next_record()) { // item found for this language

			$aResult['name']   = $this->_outFilter($oDB->f('name'));
			$aResult['idlang'] = $oDB->f('idlang');
			$aResult['online'] = $oDB->f('online');

		} else { // no item in this language found
			// fetch alternative language name
			// HerrB, 2008-04-21: Get all translations, try to use defaultLang translation, use
			// first available, otherwise. Only using defaultLang results in "ghost" elements, if 
			// created in a non-default language. See CON-110 for details. 
			
			$sSQL = "SELECT name, idlang, online FROM " . $this->table['pica_lang'] . " WHERE idpica_alloc = " . Contenido_Security::toInteger($idpica_alloc) . " ORDER BY idlang";
			$oDB->query($sSQL);
			
			$aNames = array();
			while ($oDB->next_record()) {
				$sKey = "k" . $oDB->f('idlang');
				
				$aNames[$sKey] 				= array();
				$aNames[$sKey]['name']		= $this->_outFilter($oDB->f('name'));
				$aNames[$sKey]['idlang']	= $oDB->f('idlang');
				$aNames[$sKey]['online']	= $oDB->f('online');
			}
			
			if ($aNames["k" . $this->defaultLang]) {
				// defaultLang translation available
				$aResult = $aNames["k" . $this->defaultLang];
			} else {
				// no defaultLang translation available, use first in line (reset returns first element)
				$aResult = reset($aNames);
			}
		}
		unset ($oDB);
		unset ($aNames);
		
		return $aResult;
	}
	
	function _fetchMaxOrder ($parentId = false) {
		
		if ($parentId == 'root') {
			$parentId = false;	
		}
		
		$sql = "SELECT MAX(sortorder) as max FROM " . $this->table['pica_alloc'];
		if ($parentId === false) {
			$sql .= " WHERE parentid = 0";
		} else {
			$sql .= " WHERE parentid = " . Contenido_Security::toInteger($parentId);
		}
		$this->db->query($sql);
		if ($this->db->next_record()) {
			return $this->db->f('max');
		} else {
			return 0;
		}
	}
	
	function _decreaseOrder ($parentId = false, $fromOrder) {
		$sql = "UPDATE " . $this->table['pica_alloc'] . " SET sortorder = sortorder - 1 WHERE sortorder >= " . Contenido_Security::toInteger($fromOrder);
		if ($parentId === false) {
			$sql .= " AND parentid IS NULL";	
		} else {
			$sql .= " AND parentid = " . Contenido_Security::toInteger($parentId);
		}
		$this->db->query($sql);
	}
	
	function _increaseOrder ($parentId = false, $fromOrder) {
		$sql = "UPDATE " . $this->table['pica_alloc'] . " SET sortorder = sortorder + 1 WHERE sortorder >= " . Contenido_Security::toInteger($fromOrder);
		if ($parentId === false) {
			$sql .= " AND parentid IS NULL";	
		} else {
			$sql .= " AND parentid = " . Contenido_Security::toInteger($parentId);
		}
		$this->db->query($sql);
	}
	
	function setFilters($arrInFilters = array(), $arrOutFilters = array())
	{
		$this->_arrInFilters = $arrInFilters;
		$this->_arrOutFilters = $arrOutFilters;
	}
	
	function _inFilter($data)
	{
		foreach ($this->_arrInFilters as $_function)
		{
			if (function_exists($_function))
			{
				$data = $_function($data);	
			}
		} 
		
		return $data;
	}
	
	function _outFilter($data)
	{
		foreach ($this->_arrOutFilters as $_function)
		{
			if (function_exists($_function))
			{
				$data = $_function($data);	
			}
		} 
		
		return $data;
	}
}

?>