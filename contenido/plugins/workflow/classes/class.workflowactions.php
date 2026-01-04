<?php

/**
 * This file contains the management of per-workflowitem actions.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Management of per-workflowitem actions.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @extends ItemCollection<WorkflowAction>
 */
class WorkflowActions extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_actions'), 'idworkflowaction');
        $this->_setItemClass('WorkflowAction');
    }

    /**
     * @param $idworkflowitem
     * @param $action
     * @return bool
     */
    public function get($idworkflowitem, $action)
    {
        $this->select("idworkflowitem = " . (int)$idworkflowitem . " AND action = '" . $this->escape($action) . "'");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getAvailableWorkflowActions()
    {
        $availableWorkflowActions = [
            "publish" => i18n("Publish article", "workflow"),
            "lock" => i18n("Lock article", "workflow"),
            "last" => i18n("Move back to last editor", "workflow"),
            "reject" => i18n("Reject article", "workflow"),
            "articleedit" => i18n("Edit article content", "workflow"),
            "propertyedit" => i18n("Edit article properties", "workflow"),
            "templateedit" => i18n("Edit template", "workflow"),
            "revise" => i18n("Revise article", "workflow")
        ];

        return ($availableWorkflowActions);
    }

    /**
     * @param $idworkflowitem
     * @param $action
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function set($idworkflowitem, $action)
    {
        $this->select("idworkflowitem = " . (int)$idworkflowitem . " AND action = '" . $this->escape($action) . "'");
        if (!$this->next()) {
            $newItem = $this->createNewItem();
            $newItem->setField('idworkflowitem', $idworkflowitem);
            $newItem->setField('action', $action);
            $newItem->store();
        }
    }

    /**
     * @param $idworkflowitem
     * @param $action
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function remove($idworkflowitem, $action)
    {
        $this->select("idworkflowitem = " . (int)$idworkflowitem . " AND action = '" . $this->escape($action) . "'");
        if (($item = $this->next()) !== false) {
            $this->delete($item->getField('idworkflowaction'));
        }
    }

    /**
     * Extends the where statement. See the original function for the parameters.
     *
     * @inheritDoc
     */
    public function select($where = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        return parent::select($where, $groupBy, $orderBy, $limit);
    }

}

/**
 * Class WorkflowAction
 * Class for a single workflow action
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class WorkflowAction extends Item
{

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_actions'), "idworkflowaction");
    }

}
