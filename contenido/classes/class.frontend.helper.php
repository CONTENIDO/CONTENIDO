<?php

/**
 * This file contains the frontend helper class.
 *
 * @package    Core
 * @subpackage Frontend_Util
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @package    Core
 * @subpackage Frontend_Util
 * @deprecated [2015-05-21] This class is no longer supported
 */
class cFrontendHelper
{

    /**
     * Instance of the helper class.
     *
     * @var cFrontendHelper
     */
    private static $_instance = NULL;

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public static function getInstance()
    {
        cDeprecated("The cFrontendHelper getInstance method are no longer supported.");

        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    protected function __construct()
    {
        cDeprecated("The cFrontendHelper classes are no longer supported.");
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    protected function _fetchCategoryTree($baseCategoryId, $depth, $currentCategoryId)
    {
        cDeprecated("The cFrontendHelper _fetchCategoryTree method are no longer supported.");

        if ((int)$baseCategoryId == 0) {
            throw new cUnexpectedValueException("Expect category ID greater than 0.");
        }

        $categoryHelper = cCategoryHelper::getInstance();
        $categoryHelper->setAuth(cRegistry::getAuth());

        $categoryTree = $categoryHelper->getSubCategories($baseCategoryId, $depth);

        $tree = [];

        $parentCategories = $categoryHelper->getParentCategoryIds($currentCategoryId);

        foreach ($categoryTree as $treeData) {
            $catId = $treeData['idcat'];

            $firstChildId = $lastChildId = 0;
            if (count($treeData['subcats']) > 0) {
                $lastIndex = count($treeData['subcats']) - 1;

                $firstChildId = $treeData['subcats'][0]['idcat'];
                $lastChildId = $treeData['subcats'][$lastIndex]['idcat'];
            }

            $markActive = ($currentCategoryId == $catId);
            if ($markActive == false && in_array($catId, $parentCategories)) {
                $markActive = true;
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
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public function renderNavigation($baseCategoryId, $depth, $currentCategoryId)
    {
        cDeprecated("The cFrontendHelper renderNavigation method are no longer supported.");

        $tree = $this->_fetchCategoryTree($baseCategoryId, $depth, $currentCategoryId);

        return $tree;
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public function renderSitemap($baseCategoryId, $depth, cTemplate &$tpl)
    {
        cDeprecated("The cFrontendHelper renderSitemap method are no longer supported.");

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