<?php
/**
 * Class pApiContentAllocationTreeView
 * 
 * 
 *
 * @author Marco Jahn
 * @version 0.2.0
 * @copyright four for business AG
 */

class pApiContentAllocationArticle extends pApiTree {

	var $tpl = null;
	var $template = '';

	var $load = array();

	function pApiContentAllocationArticle ($uuid) {
		global $cfg;
		
		parent::pApiTree($uuid);
		$this->tpl = new Template;
		$this->template = $cfg['pica']['treetemplate_article'];
	}
	
	function _buildRenderTree ($tree) {
		global $action, $frame, $area, $sess, $idart;
		
		$result = array();
		foreach ($tree as $item_tmp) {
			$item = array();
			
			$expandCollapseImg = 'images/spacer.gif';
			$expandCollapse = '<img src="'.$expandCollapseImg.'" border="0" style="vertical-align: middle;" width="11" height="11">';
			
			$item['ITEMNAME'] = $expandCollapse . ' ' . $item_tmp['name'];
			
			$item['ITEMINDENT'] = $item_tmp['level'] * 15 + 3;
			
			// set checked!
			#$item['CHECKBOX'] = '<input id="'.$item_tmp['idpica_alloc'].'" parentid="'.$item_tmp['parentid'].'" onClick="checkParent(this)" type="checkbox" name="allocation[]" />';
			$checked = '';
			if (in_array($item_tmp['idpica_alloc'], $this->load)) {
				$checked = ' checked="checked"';	
			}
			$item['CHECKBOX'] = '<input type="checkbox" name="allocation[]" value="'.$item_tmp['idpica_alloc'].'" '.$checked.' />';
			
			array_push($result, $item);
			
			if ($item_tmp['children']) {
				$children = $this->_buildRenderTree($item_tmp['children']);
				$result = array_merge($result, $children);	
			}
		}
		
		return $result;
	}
	
	function setChecked($load) {
		$this->load = $load;	
	}
	
	function renderTree ($return = true) {
		$this->tpl->reset();
		
		$tree = $this->fetchTree();
		if ($tree === false) {
			return false;	
		}

		$tree = $this->_buildRenderTree($tree);
		
		$even = true;
		foreach ($tree as $item) {
			$even = !$even;
			$bgcolor = ($even) ? '#FFFFFF' : '#F1F1F1';
			$this->tpl->set('d', 'BACKGROUND_COLOR', $bgcolor);
			foreach ($item as $key => $value) {
				$this->tpl->set('d', $key, $value);
			}
			$this->tpl->next();
		}
		
		$this->tpl->set('s', "CATEGORY", i18n("Category"));
		
		if ($return === true) {
			return $this->tpl->generate($this->template, true);
		} else {
			$this->tpl->generate($this->template);
		}
	}
}

?>
