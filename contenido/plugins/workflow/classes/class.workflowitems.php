<?php
/**
 * This file contains the class for workflow item management.
 *
 * @package Plugin
 * @subpackage Workflow
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
 * @method WorkflowItem createNewItem
 * @method WorkflowItem|false next
 */
class WorkflowItems extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["workflow_items"], "idworkflowitem");
        $this->_setItemClass("WorkflowItem");
    }

    /**
     * @param mixed $id
     *
     * @return bool|void
     * @throws cDbException
     * @throws cException
     */
    public function delete($id) {
        $cfg = cRegistry::getConfig();
        $item = new WorkflowItem();
        $item->loadByPrimaryKey($id);
        $pos = cSecurity::toInteger($item->get("position"));
        $idworkflow = cSecurity::toInteger($item->get("idworkflow"));
        $oDb = cRegistry::getDb();

        $this->select("position > {$pos} AND idworkflow = {$idworkflow}");
        while (($obj = $this->next()) !== false) {
            $obj->setPosition($obj->get("position") - 1);
            $obj->store();
        }

        $aUserSequencesDelete = [];
        $sSql = 'SELECT `idusersequence` FROM `%s` WHERE `idworkflowitem` = %d';
        $oDb->query($sSql, $cfg["tab"]["workflow_user_sequences"], $id);
        while ($oDb->nextRecord()) {
            $aUserSequencesDelete[] = cSecurity::toInteger($oDb->f('idusersequence'));
        }

        $sSql = 'DELETE FROM `%s` WHERE `idworkflowitem` = %d';
        $oDb->query($sSql, $cfg["tab"]["workflow_actions"], $id);

        $this->updateArtAllocation($id, 1);

        if (count($aUserSequencesDelete) > 0) {
            $sSql = 'DELETE FROM `%s` WHERE `idusersequence` IN (' . implode(',', $aUserSequencesDelete) . ')';
            $oDb->query($sSql, $cfg["tab"]["workflow_user_sequences"]);
        }
    }

    /**
     * @param int  $idworkflowitem
     * @param bool $delete
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateArtAllocation($idworkflowitem, $delete = false) {
        global $idworkflow;

        $cfg = cRegistry::getConfig();
        $oDb = cRegistry::getDb();

        $aUserSequences = [];
        $sSql = 'SELECT `idusersequence` FROM `%s` WHERE idworkflowitem = %d';
        $oDb->query($sSql, $cfg["tab"]["workflow_user_sequences"], $idworkflowitem);
        while ($oDb->nextRecord()) {
            $aUserSequences[] = cSecurity::toInteger($oDb->f('idusersequence'));
        }

        $aIdArtLang = [];
        if (count($aUserSequences) > 0) {
            $sSql = 'SELECT `idartlang` FROM `%s` WHERE `idusersequence` IN (' . implode(',', $aUserSequences) . ')';
            $oDb->query($sSql, $cfg["tab"]["workflow_art_allocation"]);
            while ($oDb->nextRecord()) {
                $aIdArtLang[] = cSecurity::toInteger($oDb->f('idartlang'));
            }
            $sSql = 'DELETE FROM `%s` WHERE `idusersequence` IN (' . implode(',', $aUserSequences) . ')';
            $oDb->query($sSql, $cfg["tab"]["workflow_art_allocation"]);
        }

        if ($delete) {
            parent::delete($idworkflowitem);
        }

        foreach ($aIdArtLang as $iIdArtLang) {
            setUserSequence($iIdArtLang, $idworkflow);
        }
    }

    /**
     * @param int $idworkflow
     * @param int $pos1
     * @param int $pos2
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function swap($idworkflow, $pos1, $pos2) {
        $idworkflow = cSecurity::toInteger($idworkflow);
        $pos1 = cSecurity::toInteger($pos1);
        $pos2 = cSecurity::toInteger($pos2);

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
        return true;
    }

    /**
     * @param int $idworkflow
     *
     * @return bool|Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idworkflow) {
        $idworkflow = cSecurity::toInteger($idworkflow);

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
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["workflow_items"], "idworkflowitem");
    }

    /**
     * @return array
     * @throws cDbException|cException
     */
    public function getStepRights() {
        $idwfi = $this->values["idworkflowitem"];
        $workflowActions = new WorkflowActions();

        $actions = $workflowActions->getAvailableWorkflowActions();

        $rights = [];
        foreach ($actions as $key => $value) {
            $rights[$key] = $workflowActions->get($idwfi, $key);
        }

        return $rights;
    }

    /**
     * Overridden setField function.
     *
     * @param string $field Void field since we override the usual setField
     *                      function
     * @param string $value Void field since we override the usual setField
     *                      function
     * @param bool $safe
     *
     * @return bool
     * @throws cInvalidArgumentException|cException
     */
    public function setField($field, $value, $safe = true) {
        if (true !== $this->isLoaded()) {
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
            $intValue = cSecurity::toInteger($value);
            $taskCollection->select("idtask = $intValue");
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
     * @param int $idposition Position of workflow item
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function init($idworkflow, $idposition) {
        $idworkflow = cSecurity::toInteger($idworkflow);
        $idposition = cSecurity::toInteger($idposition);

        $workflows = new Workflows();

        $workflows->select("idworkflow = $idworkflow");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        $workflowItems = new WorkflowItems();
        $workflowItems->select("position = $idposition AND idworkflow = $idworkflow");
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
     *
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function setPosition($idposition) {
        parent::setField("position", cSecurity::toInteger($idposition));
        $this->store();
        return true;
    }

}
