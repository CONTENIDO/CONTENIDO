<?php

/**
 * This file contains the class for workflow item management.
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
 * Class for workflow item management.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @extends ItemCollection<WorkflowItem>
 */
class WorkflowItems extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_items'), 'idworkflowitem');
        $this->_setItemClass('WorkflowItem');
    }

    /**
     * @inheritDoc
     * @param mixed $id The workflow item id.
     * @throws cDbException|cException
     */
    public function delete($id)
    {
        $item = new WorkflowItem();
        $item->loadByPrimaryKey($id);
        $pos = cSecurity::toInteger($item->get('position'));
        $workflowId = cSecurity::toInteger($item->get('idworkflow'));
        $db = cRegistry::getDb();

        $this->select("`position` > $pos AND `idworkflow` = $workflowId");
        while ($obj = $this->next()) {
            $obj->setPosition($obj->get('position') - 1);
            $obj->store();
        }

        $userSequencesToDelete = [];
        $db->query(
            'SELECT `idusersequence` FROM `%s` WHERE `idworkflowitem` = %d',
            cDb::getTableName('workflow_user_sequences'),
            $id
        );
        while ($db->nextRecord()) {
            $userSequencesToDelete[] = cSecurity::toInteger($db->f('idusersequence'));
        }

        $db->query(
            'DELETE FROM `%s` WHERE `idworkflowitem` = %d',
            cDb::getTableName('workflow_actions'),
            $id
        );

        $this->updateArtAllocation($id, 1);

        if (count($userSequencesToDelete) > 0) {
            $userSequencesToDelete = implode(',', $userSequencesToDelete);
            $db->query(
                'DELETE FROM `%s` WHERE `idusersequence` IN (' . $userSequencesToDelete . ')',
                cDb::getTableName('workflow_user_sequences')
            );
        }

        return true;
    }

    /**
     * @param int $workflowItemId
     * @param bool $delete
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateArtAllocation($workflowItemId, $delete = false)
    {
        global $idworkflow;

        $db = cRegistry::getDb();

        $userSequences = [];
        $db->query(
            'SELECT `idusersequence` FROM `%s` WHERE `idworkflowitem` = %d',
            cDb::getTableName('workflow_user_sequences'),
            $workflowItemId
        );
        while ($db->nextRecord()) {
            $userSequences[] = cSecurity::toInteger($db->f('idusersequence'));
        }

        $articleLanguageIds = [];
        if (count($userSequences) > 0) {
            $userSequences = implode(',', $userSequences);
            $db->query(
                'SELECT `idartlang` FROM `%s` WHERE `idusersequence` IN (' . $userSequences . ')',
                cDb::getTableName('workflow_art_allocation')
            );
            while ($db->nextRecord()) {
                $articleLanguageIds[] = cSecurity::toInteger($db->f('idartlang'));
            }
            $db->query(
                'DELETE FROM `%s` WHERE `idusersequence` IN (' . $userSequences . ')',
                cDb::getTableName('workflow_art_allocation')
            );
        }

        if ($delete) {
            parent::delete($workflowItemId);
        }

        foreach ($articleLanguageIds as $iIdArtLang) {
            piwf_setUserSequence($iIdArtLang, $idworkflow);
        }
    }

    /**
     * @param int $workflowId
     * @param int $pos1
     * @param int $pos2
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function swap($workflowId, $pos1, $pos2): bool
    {
        $workflowId = cSecurity::toInteger($workflowId);
        $pos1 = cSecurity::toInteger($pos1);
        $pos2 = cSecurity::toInteger($pos2);

        $this->select("`idworkflow` = {$workflowId} AND `position` = {$pos1}");
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos1ID = $item->getField('idworkflowitem');

        $this->select("`idworkflow` = {$workflowId} AND `position` = {$pos2}");
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos2ID = $item->getField('idworkflowitem');

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
     * @param int $workflowId
     * @return WorkflowItem|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($workflowId)
    {
        $workflowId = cSecurity::toInteger($workflowId);

        $workflows = new Workflows();
        $workflows->select("`idworkflow` = {$workflowId}");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Can't add item to workflow: Workflow doesn't exist", "workflow");
            return false;
        }

        $this->select("`idworkflow` = {$workflowId}", '', '`position` DESC', '1');

        $item = $this->next();

        if ($item === false) {
            $lastPos = 1;
        } else {
            $lastPos = $item->getField('position') + 1;
        }

        $newItem = $this->createNewItem();
        if ($newItem->init($workflowId, $lastPos) === false) {
            $this->delete($newItem->getField('idworkflowitem'));
            $this->lasterror = $newItem->lasterror;
            return false;
        }

        if ($item === false) {
            $this->updateArtAllocation(0);
        }

        return $newItem;
    }

}

/**
 * Class WorkflowItem
 * Class for a single workflow item
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class WorkflowItem extends Item
{

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_items'), 'idworkflowitem');
    }

    /**
     * @throws cDbException|cException
     */
    public function getStepRights(): array
    {
        $workflowItemId = $this->get('idworkflowitem');
        $workflowActions = new WorkflowActions();
        $actions = $workflowActions->getAvailableWorkflowActions();

        $rights = [];
        foreach ($actions as $key => $value) {
            $rights[$key] = $workflowActions->get($workflowItemId, $key);
        }

        return $rights;
    }

    /**
     * Overridden setField function.
     *
     * @inheritDoc
     * @throws cInvalidArgumentException|cException
     */
    public function setField($name, $value, $safe = true)
    {
        if (true !== $this->isLoaded()) {
            $this->lasterror = i18n("No item loaded", "workflow");
            return false;
        }

        if ($name === 'idsequence') {
            throw new cInvalidArgumentException("You can't set the idsequence field using this method. Use 'create' in the WorkflowItems class.");
        }

        if ($name === 'idworkflow') {
            throw new cInvalidArgumentException("You can't set the workflow ID using this method. Use 'create' in the WorkflowItems class!");
        }

        if ($name === 'position') {
            throw new cInvalidArgumentException("You can't set the position ID using this method. Use 'create' or 'swap' to create or move items!");
        }

        if ($name === 'idtask' && $value != 0) {
            $taskCollection = new WorkflowTasks();
            $intValue = cSecurity::toInteger($value);
            $taskCollection->select("`idtask` = $intValue");
            if ($taskCollection->next() === false) {
                $this->lasterror = i18n("Requested task doesn't exist, can't assign", "workflow");
                return false;
            }
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * init initializes a new wf_items entry.
     * Should only be called by the create function.
     *
     * @param int $workflowId The workflow to set the item to
     * @param int $positionId Position of workflow item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function init($workflowId, $positionId): bool
    {
        $workflowId = cSecurity::toInteger($workflowId);
        $positionId = cSecurity::toInteger($positionId);

        $workflows = new Workflows();

        $workflows->select("`idworkflow` = $workflowId");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        $workflowItems = new WorkflowItems();
        $workflowItems->select("`position` = $positionId AND `idworkflow` = $workflowId");
        if ($workflowItems->next()) {
            $this->lasterror = i18n("Position in this workflow already exists.", "workflow");
            return false;
        }

        parent::setField('idworkflow', $workflowId);
        parent::setField('position', $positionId);
        $this->store();
        return true;
    }

    /**
     * setPosition Sets the position for an item.
     * Should only be called by the "swap" function
     *
     * @param int $positionId The new position ID
     * @throws cDbException|cInvalidArgumentException
     */
    public function setPosition($positionId): bool
    {
        parent::setField("position", cSecurity::toInteger($positionId));
        $this->store();
        return true;
    }

}
