<?php
/**
 * This file contains the frontend helper class.
 *
 * @package       Core
 * @subpackage    Helper
 *
 * @author        Dominik Ziegler
 * @copyright     four for business AG <www.4fb.de>
 * @license       http://www.contenido.org/license/LIZENZ.txt
 * @link          http://www.4fb.de
 * @link          http://www.contenido.org
 */

/**
 * This class contains functions for the frontend helper class in CONTENIDO.
 *
 * @package       Core
 * @subpackage    Helper
 */
class cFrontendHelper {
    /**
     * Instance of the helper class.
     * @var cFrontendHelper
     */
    static private $_instance = NULL;

    /**
     * Returns the instance of this class.
     * @return    cFrontendHelper
     */
    public static function getInstance() {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor of the class.
     * @return    void
     */
    protected function __construct() {
    }
	
	/** 
	 * Fetches the requested category tree.
	 * @param	int			$baseCategoryId		root category ID
	 * @param	int			$depth				maximum depth
	 * @param	int			$currentCategoryId	the current category ID
	 * @return	array	category tree
	 */
	protected function _fetchCategoryTree($baseCategoryId, $depth, $currentCategoryId) {
		if ((int) $baseCategoryId == 0) {
			throw new cUnexpectedValueException("Expect category ID greater than 0.");
		}
		
		$categoryHelper = cCategoryHelper::getInstance();
		$categoryHelper->setAuth(cRegistry::getAuth());
		
		$categoryTree = $categoryHelper->getSubCategories($baseCategoryId, $depth);
		
		$tree = array();
		
		foreach ($categoryTree as $treeData) {
			$catId = $treeData['idcat'];
			
			$firstChildId = $lastChildId = 0;
			if (count($treeData['subcats']) > 0) {
				$lastIndex = count($treeData['subcats']) - 1;
				
				$firstChildId = $treeData['subcats'][0]['idcat'];
				$lastChildId = $treeData['subcats'][$lastIndex]['idcat'];
			}
			
			$markActive = ($currentCategoryId == $catId);
			if ($markActive == false) {
				$parentCategories = $categoryHelper->getParentCategoryIds($currentCategoryId);
				if (in_array($catId, $parentCategories)) {
					$markActive = true;
				}
			}
			
			$treeItem['first_child_id'] = $firstChildId;
			$treeItem['last_child_id'] = $lastChildId;
			$treeItem['tree_data'] = $treeData;
			$treeItem['active'] = $markActive;
			$tree[] = $treeItem;
		}
		
		return $tree;
	}
	
	/**
	 * Helper function to render the navigation.
	 * @param	int			$baseCategoryId		root category ID
	 * @param	int			$depth				maximum depth
	 * @param	int			$currentCategoryId	the current category ID
	 * @param	cTemplate	$tpl				template reference
	 * @return	void
	 */
	public function renderNavigation($baseCategoryId, $depth, $currentCategoryId, cTemplate &$tpl) {
		$tree = $this->_fetchCategoryTree($baseCategoryId, $depth, $currentCategoryId);
		
		foreach ($tree as $treeItem) {
			$treeData = $treeItem['tree_data'];
			$catId = $treeData['idcat'];
			
			$firstChildId = $treeItem['first_child_id'];
			$lastChildId = $treeItem['last_child_id'];

			$tpl->set('d', 'name', $treeData['item']->getField('name'));
			$tpl->set('d', 'css_level', $treeData['level']);
			$tpl->set('d', 'css_first_item', ($firstChildId == $catId ? ' first' : ''));
			$tpl->set('d', 'css_last_item', ($lastChildId == $catId ? ' last' : ''));
			$tpl->set('d', 'css_active_item', ($markActive === true ? ' active' : ''));
			$tpl->set('d', 'url', $treeData['item']->getLink());
			$tpl->next();
			
			if ($markActive === true && $firstChildId != 0) {
				$this->renderNavigation($catId, $depth, $currentCategoryId, $tpl);
			}
		}
	}
	
	/**
	 * Helper function to render the sitemap.
	 * @param	int			$baseCategoryId		root category ID
	 * @param	int			$depth				maximum depth
	 * @param	cTemplate	$tpl				template reference
	 * @return	void
	 */
	public function renderSitemap($baseCategoryId, $depth, cTemplate &$tpl) {
		$tree = $this->_fetchCategoryTree($baseCategoryId, $depth, 0);
		
		foreach ($tree as $treeItem) {
			$treeData = $treeItem['tree_data'];
			$catId = $treeData['idcat'];
			
			$firstChildId = $treeItem['first_child_id'];

			$tpl->set('d', 'name', $treeData['item']->getField('name'));
			$tpl->set('d', 'css_level', $treeData['level']);
			$tpl->set('d', 'url', $treeData['item']->getLink());
			$tpl->next();
			
			if ($firstChildId != 0) {
				$this->renderSitemap($catId, $depth, $tpl);
			}
		}
	}
}