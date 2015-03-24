<?php
/**
 * This file contains the management of per-workflowitem actions.
 *
 * @package Plugin
 * @subpackage Workflow
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Management of per-workflowitem actions.
 *
 * @package Plugin
 * @subpackage Workflow
 */
class WorkflowActions extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow_actions"], "idworkflowaction");
        $this->_setItemClass("WorkflowAction");
    }

    public function get($idworkflowitem, $action) {
        $this->select("idworkflowitem = " . (int) $idworkflowitem . " AND action = '" . $this->escape($action) . "'");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    public function getAvailableWorkflowActions() {
        $availableWorkflowActions = array(
            "publish" => i18n("Publish article", "workflow"),
            "lock" => i18n("Lock article", "workflow"),
            "last" => i18n("Move back to last editor", "workflow"),
            "reject" => i18n("Reject article", "workflow"),
            "articleedit" => i18n("Edit article content", "workflow"),
            "propertyedit" => i18n("Edit article properties", "workflow"),
            "templateedit" => i18n("Edit template", "workflow"),
            "revise" => i18n("Revise article", "workflow")
        );

        return ($availableWorkflowActions);
    }

    public function set($idworkflowitem, $action) {
        $this->select("idworkflowitem = " . (int) $idworkflowitem . " AND action = '" . $this->escape($action) . "'");
        if (!$this->next()) {
            $newitem = $this->createNewItem();
            $newitem->setField("idworkflowitem", $idworkflowitem);
            $newitem->setField("action", $action);
            $newitem->store();
        }
    }

    public function remove($idworkflowitem, $action) {
        $this->select("idworkflowitem = " . (int) $idworkflowitem . " AND action = '" . $this->escape($action) . "'");
        if (($item = $this->next()) !== false) {
            $this->delete($item->getField("idworkflowaction"));
        }
    }

    public function select($where = "", $group_by = "", $order_by = "", $limit = "") {
        global $client;

        return parent::select($where, $group_by, $order_by, $limit);
    }

}

/**
 * Class WorkflowAction
 * Class for a single workflow action
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowAction extends Item {

    /**
     * Constructor Function
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;

        parent::__construct($cfg["tab"]["workflow_actions"], "idworkflowaction");
    }

}

?>