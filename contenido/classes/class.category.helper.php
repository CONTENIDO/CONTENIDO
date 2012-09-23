<?php
/**
 * This file contains the category helper class.
 *
 * @package			Core
 * @subpackage		Helper
 * @version			1.0
 *
 * @author			Dominik Ziegler
 * @copyright		four for business AG <www.4fb.de>
 * @license			http://www.contenido.org/license/LIZENZ.txt
 * @link			http://www.4fb.de
 * @link			http://www.contenido.org
 */

/**
 * @package			Core
 * @subpackage		Helper
 *
 * This class contains functions for the category helper class in CONTENIDO.
 */
class cCategoryHelper {
	/**
	 * Instance of the helper class.
	 * @var cCategoryHelper
	 */
	static private $_instance = NULL;
	
	/**
	 * Local stored language ID 
	 * @var	int	language ID
	 */
	protected $_languageId = 0;
	
	/**
	 * Local stored client ID 
	 * @var	int	client ID
	 */
	protected $_clientId = 0;
	
	/** 
	 * Local cache of category levels.
	 * @var	array
	 */
	protected $_levelCache = array();
	
	/**
	 * Returns the instance of this class.
	 * @return	cCategoryHelper
	 */
	public static function getInstance() {
		if (self::$_instance === NULL) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	/**
	 * Constructor of the class.
	 * @return	void
	 */
	protected function __construct() {
	}
	
	/**
	 * Sets the client ID to store it locally in the class.
	 * @param	int	$clientId	client ID
	 * @return	void
	 */
	public function setClientId($clientId = 0) {
		$this->_clientId = (int) $clientId;
	}
	
	/**
	 * Returns the local stored client ID
	 * @return	int	client ID
	 */
	public function getClientId() {
		if ($this->_clientId == 0) {
			$clientId = cRegistry::getClientId();
			if ($clientId == 0) {
				throw new cInvalidArgumentException("No active client ID specified or found.");
			}
			
			return $clientId;
		}
		
		return $this->_clientId;
	}
	
	/**
	 * Sets the language ID to store it locally in the class.
	 * @param	int	$languageId	language ID
	 * @return	void
	 */
	public function setLanguageId($languageId = 0) {
		$this->_languageId = (int) $languageId;
	}
	
	/**
	 * Returns the local stored language ID
	 * @return	int	language ID
	 */
	public function getLanguageId() {
		if ($this->_languageId == 0) {
			$languageId = cRegistry::getLanguageId();
			if ($languageId == 0) {
				throw new cInvalidArgumentException("No active language ID specified or found.");
			}
			
			return $languageId;
		}
		
		return $this->_languageId;
	}
	
	/**
	 * Return the ID of the top most category based on a given category ID.
	 * @param	int	$categoryId	Base category ID to search on
	 * @return	int	Top most category ID 
	 */
	public function getTopMostCategoryId($categoryId) {
		$category = new cApiCategory($categoryId);

		if ($category->get('parentid') == 0) {
			$topMostCategoryId = $categoryId;
		} else {
			$topMostCategoryId = $this->getTopMostCategoryId($category->get('parentid'));
		}

		return $topMostCategoryId;
	}
	
	/** 
	 * Returns an array with ordered cApiCategoryLanguage objects e.g. for a breadcrumb.
	 * @param	int	$categoryId	Last category ID in list.
	 * @param	int	$startingLevel	Define here, at which level the list should start. (optional, default: 1)
	 * @param	int	$maxDepth	Amount of the max depth of categories. (optional, default: 20)
	 * @return	array	Array with cApiCategoryLanguage objects
	 */
	public function getCategoryPath($categoryId, $startingLevel = 1, $maxDepth = 20) {
		$languageId = $this->getLanguageId();
		
		$categories = array();
		
		$categoryLanguage = new cApiCategoryLanguage();
		$categoryLanguage->loadByCategoryIdAndLanguageId($categoryId, $languageId);
		
		$categories[] = $categoryLanguage;
	
		$parentCategoryIds = $this->getParentCategoryIds($categoryId, $maxDepth);
		foreach ($parentCategoryIds as $parentCategoryId) {
			$categoryLanguage = new cApiCategoryLanguage();
			$categoryLanguage->loadByCategoryIdAndLanguageId($parentCategoryId, $languageId);
			
			$categories[] = $categoryLanguage;
		}
		
		for ($removeCount = 2; $removeCount <= $startingLevel; $removeCount++) {
			array_pop($categories);
		}

		return array_reverse($categories);
	}
	
	/** 
	 * Fetch all parent category IDs of a given category.
	 * @param	int	$categoryId	Base category to search on.
	 * @param	int	$maxDepth	Amount of the max depth of categories. (optional, default: 20)
	 * @return	array	Array with parent category IDs.
	 */
	public function getParentCategoryIds($categoryId, $maxDepth = 20) {
		$categoryIds = array();
		
		$nextCategoryId = $categoryId;
		
		$categoryCount = 1;
		while ($nextCategoryId != 0 && $categoryCount < $maxDepth) {
			$category = new cApiCategory($nextCategoryId);
			
			$nextCategoryId = $category->get('parentid');
			if ($nextCategoryId != 0) {
				$categoryIds[] = $nextCategoryId;
			}
			$categoryCount++;
		}
		
		return $categoryIds;
	}
	
	/** 
	 * Fetchs the level of a category by a given category ID.
	 * @param	int	$categoryId	Category ID to fetch the level of.
	 * @return	int	category level
	 */
	public function getCategoryLevel($categoryId) {
		if (isset($this->_levelCache[$categoryId]) === false) {
			$categoryTree = new cApiCategoryTree();
			$categoryTree->loadBy("idcat", $categoryId);
			
			if ($categoryTree->isLoaded() === false) {
				return -1;
			}
			
			$level = $categoryTree->get('level');
			
			$this->_levelCache[$categoryId] = $level;
		}
		
		return $this->_levelCache[$categoryId];
	}
}