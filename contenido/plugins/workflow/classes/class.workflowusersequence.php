<?php

/**
 * This file contains the class for workflow user sequence managements.
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
 * Class for workflow user sequence management.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @extends ItemCollection<WorkflowUserSequence>
 */
class WorkflowUserSequences extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_user_sequences'), 'idusersequence');
        $this->_setItemClass('WorkflowUserSequence');
    }

    /**
     * @inheritDoc
     * @param int $id The workflow user sequence id.
     * @throws cDbException|cException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);
        $item = new WorkflowUserSequence();
        $item->loadByPrimaryKey($id);

        $pos = $item->get('position');
        $workflowItemId = cSecurity::toInteger($item->get('idworkflowitem'));
        $this->select("`position` > $pos AND `idworkflowitem` = $workflowItemId");
        while ($obj = $this->next()) {
            $pos = $obj->get('position') - 1;
            $obj->setPosition($pos);
            $obj->store();
        }

        parent::delete($id);

        $this->updateArtAllocation($id);

        return true;
    }

    /**
     * @param int $userSequenceId
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateArtAllocation($userSequenceId)
    {
        global $idworkflow;

        $userSequenceId = cSecurity::toInteger($userSequenceId);
        $db = cRegistry::getDb();

        $articleLanguageIds = [];
        $db->query(
            'SELECT `idartlang` FROM `%s` WHERE `idusersequence` = %d',
            cDb::getTableName('workflow_art_allocation'),
            $userSequenceId
        );
        while ($db->nextRecord()) {
            $articleLanguageIds[] = cSecurity::toInteger($db->f('idartlang'));
        }

        $db->query(
            'DELETE FROM `%s` WHERE `idusersequence` = %d',
            cDb::getTableName('workflow_art_allocation'),
            $userSequenceId
        );
        foreach ($articleLanguageIds as $iIdArtLang) {
            piwf_setUserSequence($iIdArtLang, $idworkflow);
        }
    }

    /**
     * @param int $workflowItemId
     * @return WorkflowUserSequence|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($workflowItemId)
    {
        $workflowItemId = cSecurity::toInteger($workflowItemId);
        $workflowItems = new WorkflowItems();
        if (!$workflowItems->exists($workflowItemId)) {
            $this->lasterror = i18n("Workflow item doesn't exist. Can't create entry.", "workflow");
            return false;
        }

        $this->select("`idworkflowitem` = " . $workflowItemId, '', '`position` DESC', '1');

        $item = $this->next();

        if ($item === false) {
            $lastPos = 1;
        } else {
            $lastPos = $item->getField('position') + 1;
        }

        $newItem = $this->createNewItem();
        $newItem->setWorkflowItem($workflowItemId);
        $newItem->setPosition($lastPos);
        $newItem->store();

        return $newItem;
    }

    /**
     * @param int $workflowItemId
     * @param int $pos1
     * @param int $pos2
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function swap($workflowItemId, $pos1, $pos2): bool
    {
        $workflowItemId = cSecurity::toInteger($workflowItemId);
        $pos1 = cSecurity::toInteger($pos1);
        $pos2 = cSecurity::toInteger($pos2);

        $this->select("`idworkflowitem` = $workflowItemId AND `position` = $pos1");
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos1ID = $item->getField('idusersequence');

        $this->select("`idworkflowitem` = $workflowItemId AND `position` = $pos2");
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos2ID = $item->getField('idusersequence');

        $item = new WorkflowUserSequence();
        $item->loadByPrimaryKey($pos1ID);
        $item->setPosition($pos2);
        $item->store();
        $item->loadByPrimaryKey($pos2ID);
        $item->setPosition($pos1);
        $item->store();

        $this->updateArtAllocation($pos2ID);
        $this->updateArtAllocation($pos1ID);

        return true;
    }

}

/**
 * Class WorkflowUserSequence
 * Class for a single workflow item
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class WorkflowUserSequence extends Item
{

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_user_sequences'), 'idusersequence');
    }

    /**
     * Override setField Function to prevent that somebody modifies idsequence.
     *
     * @inheritDoc
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setField($name, $value, $safe = true)
    {
        $userSequenceId = false;
        switch ($name) {
            case 'idworkflowitem':
                throw new cInvalidArgumentException(
                    'Please use create to modify idsequence. Direct modifications are not allowed'
                );
            case 'idusersequence':
                throw new cInvalidArgumentException(
                    'Please use create to modify idsequence. Direct modifications are not allowed'
                );
            case 'position':
                throw new cInvalidArgumentException(
                    'Please use create and swap to set the position. Direct modifications are not allowed'
                );
            case 'iduser':
                if ($value != 0) {
                    $db = cRegistry::getDb();

                    $sql = "SELECT `user_id` FROM `%s` WHERE `user_id` = '%s'";
                    $db->query($sql, cDb::getTableName('user'), $value);
                    if (!$db->nextRecord()) {
                        $sql = "SELECT `group_id` FROM `%s` WHERE `group_id` = '%s'";
                        $db->query($sql, cDb::getTableName('groups'), $value);
                        if (!$db->nextRecord()) {
                            $this->lasterror = i18n("Can't set user_id: User or group doesn't exist", "workflow");
                            return false;
                        }
                    }
                    $userSequenceId = parent::getField('idusersequence');
                }
        }

        $result = parent::setField($name, $value, $safe);
        if ($userSequenceId) {
            $workflowUserSequences = new WorkflowUserSequences();
            $workflowUserSequences->updateArtAllocation(0);
        }

        return $result;
    }

    /**
     * Returns the associated workflowItem for this user sequence
     *
     * @return bool|WorkflowItem
     * @throws cDbException|cException
     */
    public function getWorkflowItem()
    {
        if ($this->isLoaded()) {
            $workflowItem = new WorkflowItem();
            $workflowItem->loadByPrimaryKey($this->values['idworkflowitem']);
            return $workflowItem;
        } else {
            return false;
        }
    }

    /**
     * Interface to set idworkflowitem.
     * Should only be called by "create".
     *
     * @param int $value The value to set
     */
    public function setWorkflowItem($value)
    {
        parent::setField('idworkflowitem', cSecurity::toInteger($value));
    }

    /**
     * Interface to set position.
     * Should only be called by "create".
     *
     * @param int $value The value to set
     */
    public function setPosition($value)
    {
        parent::setField('position', cSecurity::toInteger($value));
    }

}
