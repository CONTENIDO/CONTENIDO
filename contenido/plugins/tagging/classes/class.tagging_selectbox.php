<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Render selectbox witzh complete tagging tree
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Plugins
 * @version    0.2.1
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id: class.tagging_selectbox.php 1711 2011-11-17 23:17:27Z xmurrix $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * @package    CONTENIDO Plugins
 * @subpackage Tagging
 */
class pApiTaggingSelectBox extends pApiTree {

	var $idSetter = true;
	var $load = array();

	function pApiTaggingComplexList ($uuid) {
		global $cfg;
		
		parent::pApiTree($uuid);
	}
	
	function _buildRenderTree ($tree) {
		global $action, $frame, $area, $sess, $idart;
		
		$oldIdSetter = $this->idSetter;
		$this->idSetter = false;
		
		$result = '';
		
		$levelElms = sizeof($tree);
		$cnt = 1;
		foreach ($tree as $item_tmp) {
			$item = '';
		
			$spacer = '|-';
			$spacer = str_pad($spacer, (($item_tmp['level'] + 1) * 2), "--", STR_PAD_RIGHT);
			
			$result .= '<option value="'.$item_tmp['idpica_alloc'].'_'.$item_tmp['level'].'">'.$spacer . $item_tmp['name'].'</option>';
			
			if ($item_tmp['children']) {
				$children = $this->_buildRenderTree($item_tmp['children']);
				$result .= $children;	
			}
		}

		return $result;
	}
	
	function setChecked($load) {
		return false;	
	}
	
	/**
	 * 
	 * @modified 27.10.2005 $bUseTreeStatus = false (tagging tree in selectbox is always expanded)
	 */
	function renderTree ($return = true, $parentId = false, $bUseTreeStatus = false) {
		
		$tree = $this->fetchTree($parentId, 0, $bUseTreeStatus);
		
		if ($tree === false) {
			return false;	
		}

		$tree = $this->_buildRenderTree($tree);
		
		if ($return === true) {
			return $tree;
		} else {
			echo $tree;	
		}
	}
}

?>