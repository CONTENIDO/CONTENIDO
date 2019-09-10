<?php

/**
 * This file contains the select box class for the plugin content allocation.
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
 * Select box class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocationSelectBox extends pApiTree {

    /**
     * @var bool
     */
    protected $_idSetter = true;

    /**
     * @var array
     */
    protected $_load = array();

    /**
     * pApiContentAllocationSelectBox constructor
     *
     * @param string $uuid
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($uuid) {
        parent::__construct($uuid);
    }

    /**
     * Old constructor
     *
     * @deprecated [2016-02-11]
     *                This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     *
     * @param string $uuid
     *
     * @return pApiContentAllocationSelectBox
     * @throws cDbException
     * @throws cException
     */
    public function pApiContentAllocationSelectBox($uuid) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        return $this->__construct($uuid);
    }

    /**
     * Builed an render tree
     *
     * @param $tree
     * @return string
     */
    protected function _buildRenderTree($tree) {

        $this->_idSetter = false;
        $result = '';

        foreach ($tree as $item_tmp) {
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

    /**
     * Old function
     *
     * @deprecated [2016-02-11]
     * 				This method is deprecated and is not needed any longer.    *
     * @param null $load
     * @return bool
     */
    public function setChecked($load = null) {
        cDeprecated('This method is deprecated and is not needed any longer.');
        return false;
    }

    /**
     * Render tree
     *
     * @param bool $return
     * @param mixed   $parentId
     * @param bool $useTreeStatus (if true use expand/collapsed status of the tree, otherwise not)
     *
     * @return bool|object
     * @throws cDbException
     */
    public function renderTree($return = true, $parentId = false, $useTreeStatus = false) {

        $tree = $this->fetchTree($parentId, 0, $useTreeStatus);

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
