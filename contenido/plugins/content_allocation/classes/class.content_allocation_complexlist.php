<?php
/**
 * This file contains the complex list class for the plugin content allocation.
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
 * Complex list class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
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
                $li_closeElm = 'class="lastElem"';
            }
            $cnt++;

            $even = !$even;
            $bgcolor = ($even) ? 'bright' : 'dark';

            // for wrapping purposes
            $item_tmp['name'] = str_replace('-', '- ', $item_tmp['name']);

            $checkbox = '<input type="checkbox" name="allocation[]" onClick="addToList(this);" ' . $checked . '" id="e'.$item_tmp['idpica_alloc'].'" value="'.$item_tmp['idpica_alloc'].'">';
            $item = "\n<li baseClass=\"" . $bgcolor . "\" ".$li_closeElm.">" . $checkbox . " " . $item_tmp['name'];

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