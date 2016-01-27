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
     * @modified 27.10.2005 $bUseTreeStatus = false (ContentAllocation tree in selectbox is always expanded)
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