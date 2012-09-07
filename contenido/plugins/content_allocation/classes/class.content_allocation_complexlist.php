<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Content Allocation
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend plugins
 * @version    0.2.1
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
 *   $Id: class.content_allocation_complexlist.php 538 2008-07-02 14:12:32Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class pApiContentAllocationComplexList extends pApiTree {

	var $idSetter = true;
	var $load = array();

	function pApiContentAllocationComplexList ($uuid) {
		global $cfg;
		parent::pApiTree($uuid);
	}
	
	function _buildRenderTree ($tree) {
		global $action, $frame, $area, $sess, $idart;
		
		$oldIdSetter = $this->idSetter;
		$this->idSetter = false;
		
		$result = '';
		
		$even = true;

		$levelElms = sizeof($tree);
		$cnt = 1;
		foreach ($tree as $item_tmp) {
			$item = '';
			$checked = '';
			if (in_array($item_tmp['idpica_alloc'], $this->load)) {
				$checked = ' checked="checked"';	
			}
			
			$li_closeElm = '';
			if ($cnt == $levelElms) {
				$li_closeElm = 'style="border-bottom: 0;"';
			}
			$cnt++;
			
			$even = !$even;
			$bgcolor = ($even) ? 'bright' : 'dark';
			
			// for wrapping purposes
			$item_tmp['name'] = str_replace('-', '- ', $item_tmp['name']);
			
			$checkbox = '<input type="checkbox" name="allocation[]" onClick="addToList(this);" ' . $checked . '" id="e'.$item_tmp['idpica_alloc'].'" value="'.$item_tmp['idpica_alloc'].'" />';
			$item = "\n<li style=\"border-bottom: 1px solid #B3B3B3\" baseClass=\"" . $bgcolor . "\" ".$li_closeElm.">" . $checkbox . " " . $item_tmp['name'];
			
			$result .= $item;
			
			if ($item_tmp['children']) {
				$children = $this->_buildRenderTree($item_tmp['children']);
				$result .= "\n<ul>" . $children . "</li>";	
			} else {
				$result .= "\n</li>";	
			}
		}

		if ($oldIdSetter === true) {
			return "\n<ul id=\"finder\">" . $result . "\n</ul>";
		} else {
			return $result . "\n</ul>";
		}
	}
	
	function setChecked($load) {
		$this->load = $load;	
	}
	
	function renderTree ($return = true) {
		$tree = $this->fetchTree();
		if ($tree === false) {
			return false;	
		}

		$tree = $this->_buildRenderTree($tree);
		if ($return === true) {
			return $tree;
		}
	}
}

?>