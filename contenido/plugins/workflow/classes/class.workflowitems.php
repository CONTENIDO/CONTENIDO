<?php
/**
 * This file contains the class for workflow item management.
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
 * Class for workflow item management.
 *
 * @package Plugin
 * @subpackage Workflow
 */
class WorkflowItems extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow_items"], "idworkflowitem");
        $this->_setItemClass("WorkflowItem");
    }

    public function delete($id) {
        global $cfg;
        $item = new WorkflowItem();
        $item->loadByPrimaryKey($id);
        $pos = (int) $item->get("position");
        $idworkflow = (int) $item->get("idworkflow");
        $oDb = cRegistry::getDb();

        $this->select("position > {$pos} AND idworkflow = {$idworkflow}");
        while (($obj = $this->next()) !== false) {
            $obj->setPosition($obj->get("position") - 1);
            $obj->store();
        }

        $aUserSequencesDelete = array();
        $sSql = 'SELECT idusersequence FROM ' . $cfg["tab"]["workflow_user_sequences"] . ' WHERE idworkflowitem = ' . (int) $id;
        $oDb->query($sSql);
        while ($oDb->nextRecord()) {
            $aUserSequencesDelete[] = (int) $oDb->f('idusersequence');
        }

        $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_actions"] . ' WHERE idworkflowitem = ' . (int) $id;
        $oDb->query($sSql);

        $this->updateArtAllocation($id, 1);

        if (count($aUserSequencesDelete) > 0) {
            $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_user_sequences"] . ' WHERE idusersequence in (' . implode(',', $aUserSequencesDelete) . ')';
            $oDb->query($sSql);
        }
    }

    public function updateArtAllocation($idworkflowitem, $delete = false) {
        global $idworkflow, $cfg;
        $oDb = cRegistry::getDb();

        $aUserSequences = array();
        $sSql = 'SELECT idusersequence FROM ' . $cfg["tab"]["workflow_user_sequences"] . ' WHERE idworkflowitem = ' . (int) $idworkflowitem;

        $oDb->query($sSql);
        while ($oDb->nextRecord()) {
            $aUserSequences[] = (int) $oDb->f('idusersequence');
        }

        $aIdArtLang = array();
        if (count($aUserSequences) > 0) {
            $sSql = 'SELECT idartlang FROM ' . $cfg["tab"]["workflow_art_allocation"] . ' WHERE idusersequence in (' . implode(',', $aUserSequences) . ')';
            $oDb->query($sSql);
            while ($oDb->nextRecord()) {
                $aIdArtLang[] = (int) $oDb->f('idartlang');
            }
            $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_art_allocation"] . ' WHERE idusersequence in (' . implode(',', $aUserSequences) . ')';
            $oDb->query($sSql);
        }

        if ($delete) {
            parent::delete($idworkflowitem);
        }

        foreach ($aIdArtLang as $iIdArtLang) {
            setUserSequence($iIdArtLang, $idworkflow);
        }
    }

    public function swap($idworkflow, $pos1, $pos2) {
        $idworkflow = (int) $idworkflow;
        $pos1 = (int) $pos1;
        $pos2 = (int) $pos2;

        $this->select("idworkflow = {$idworkflow} AND position = {$pos1}");
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos1ID = $item->getField("idworkflowitem");

        $this->select("idworkflow = {$idworkflow} AND position = {$pos2}");
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos2ID = $item->getField("idworkflowitem");

        $item = new WorkflowItem();
        $item->loadByPrimaryKey($pos1ID);
        $item->setPosition($pos2);
        $item->store();
        $item->loadByPrimaryKey($pos2ID);
        $item->setPosition($pos1);
        $item->store();

        $this->updateArtAllocation($pos1ID);
        $this->updateArtAllocation($pos2ID);
        return (true);
    }

    public function create($idworkflow) {
        $idworkflow = (int) $idworkflow;

        $workflows = new Workflows();
        $workflows->select("idworkflow = {$idworkflow}");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Can't add item to workflow: Workflow doesn't exist", "workflow");
            return false;
        }

        $this->select("idworkflow = {$idworkflow}", "", "position DESC", "1");

        $item = $this->next();

        if ($item === false) {
            $lastPos = 1;
        } else {
            $lastPos = $item->getField("position") + 1;
        }

        $newItem = $this->createNewItem();
        if ($newItem->init($idworkflow, $lastPos) === false) {
            $this->delete($newItem->getField("idworkflowitem"));
            $this->lasterror = $newItem->lasterror;
            return false;
        }

        if ($item === false) {
            $this->updateArtAllocation(0);
        }

        return ($newItem);
    }

}

/**
 * Class WorkflowItem
 * Class for a single workflow item
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowItem extends Item {

    /**
     * Constructor Function
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;

        parent::__construct($cfg["tab"]["workflow_items"], "idworkflowitem");
    }

    public function getStepRights() {
        $idwfi = $this->values["idworkflowitem"];
        $workflowActions = new WorkflowActions();

        $actions = $workflowActions->getAvailableWorkflowActions();

        foreach ($actions as $key => $value) {
            $rights[$key] = $workflowActions->get($idwfi, $key);
        }

        return $rights;
    }

    /**
     * Overridden setField function.
     *
     * @param string $field Void field since we override the usual setField
     *            function
     * @param string $value Void field since we override the usual setField
     *            function
     * @throws cInvalidArgumentException if the field is idsequence, idworkflow
     *         or position
     */
    public function setField($field, $value, $safe = true) {
        if ($this->virgin == true) {
            $this->lasterror = i18n("No item loaded", "workflow");
            return false;
        }

        if ($field == "idsequence") {
            throw new cInvalidArgumentException("You can't set the idsequence field using this method. Use 'create' in the WorkflowItems class.");
        }

        if ($field == "idworkflow") {
            throw new cInvalidArgumentException("You can't set the workflow ID using this method. Use 'create' in the WorkflowItems class!");
        }

        if ($field == "position") {
            throw new cInvalidArgumentException("You can't set the position ID using this method. Use 'create' or 'swap' to create or move items!");
        }

        if ($field == "idtask" && $value != 0) {
            $taskCollection = new WorkflowTasks();
            $taskCollection->select("idtask = '$value'");
            if ($taskCollection->next() === false) {
                $this->lasterror = i18n("Requested task doesn't exist, can't assign", "workflow");
                return false;
            }
        }

        parent::setField($field, $value, $safe);
    }

    /**
     * init initializes a new wf_items entry.
     * Should
     * only be called by the create function.
     *
     * @param int $idworkflow The workflow to set the item to
     */
    public function init($idworkflow, $idposition) {
        global $cfg;

        $workflows = new Workflows();

        $workflows->select("idworkflow = '$idworkflow'");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        $workflowItems = new WorkflowItems();
        $workflowItems->select("position = '$idposition' AND idworkflow = '$idworkflow'");
        if ($workflowItems->next()) {
            $this->lasterror = i18n("Position in this workflow already exists.", "workflow");
            return false;
        }

        parent::setField("idworkflow", $idworkflow);
        parent::setField("position", $idposition);
        $this->store();
        return true;
    }

    /**
     * setPosition Sets the position for an item.
     * Should only be
     * called by the "swap" function
     *
     * @param int $idposition The new position ID
     */
    public function setPosition($idposition) {
        parent::setField("position", $idposition);
        $this->store();
        return true;
    }

}

?>