<?php
/**
 * This file contains the class for workflow user sequence managements.
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for workflow user sequence management.
 *
 * @package Plugin
 * @subpackage Workflow
 * @method WorkflowUserSequence createNewItem
 * @method WorkflowUserSequence|bool next
 */
class WorkflowUserSequences extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('workflow_user_sequences'), "idusersequence");
        $this->_setItemClass("WorkflowUserSequence");
    }

    /**
     * @param int $id
     *
     * @return bool|void
     * @throws cDbException
     * @throws cException
     */
    public function delete($id) {
        $id = cSecurity::toInteger($id);
        $item = new WorkflowUserSequence();
        $item->loadByPrimaryKey($id);

        $pos = $item->get("position");
        $idworkflowitem = cSecurity::toInteger($item->get("idworkflowitem"));
        $this->select("position > $pos AND idworkflowitem = " . $idworkflowitem);
        while (($obj = $this->next()) !== false) {
            $pos = $obj->get("position") - 1;
            $obj->setPosition($pos);
            $obj->store();
        }

        parent::delete($id);

        $this->updateArtAllocation($id);

        return true;
    }

    /**
     * @param int $idusersequence
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateArtAllocation($idusersequence) {
        global $idworkflow;

        $idusersequence = cSecurity::toInteger($idusersequence);
        $oDb = cRegistry::getDb();

        $aIdArtLang = [];
        $sSql = 'SELECT `idartlang` FROM `%s` WHERE `idusersequence` = %d';
        $oDb->query($sSql, cRegistry::getDbTableName('workflow_art_allocation'), $idusersequence);
        while ($oDb->nextRecord()) {
            $aIdArtLang[] = cSecurity::toInteger($oDb->f('idartlang'));
        }

        $sSql = 'DELETE FROM `%s` WHERE `idusersequence` = %d';
        $oDb->query($sSql, cRegistry::getDbTableName('workflow_art_allocation'), $idusersequence);

        foreach ($aIdArtLang as $iIdArtLang) {
            setUserSequence($iIdArtLang, $idworkflow);
        }
    }

    /**
     * @param int $idworkflowitem
     *
     * @return bool|Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idworkflowitem) {
        $idworkflowitem = cSecurity::toInteger($idworkflowitem);
        $workflowItems = new WorkflowItems();
        if (!$workflowItems->exists($idworkflowitem)) {
            $this->lasterror = i18n("Workflow item doesn't exist. Can't create entry.", "workflow");
            return false;
        }

        $this->select("idworkflowitem = " . $idworkflowitem, "", "position DESC", "1");

        $item = $this->next();

        if ($item === false) {
            $lastPos = 1;
        } else {
            $lastPos = $item->getField("position") + 1;
        }

        $newItem = $this->createNewItem();
        $newItem->setWorkflowItem($idworkflowitem);
        $newItem->setPosition($lastPos);
        $newItem->store();

        return $newItem;
    }

    /**
     * @param int $idworkflowitem
     * @param int $pos1
     * @param int $pos2
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function swap($idworkflowitem, $pos1, $pos2) {
        $idworkflowitem = cSecurity::toInteger($idworkflowitem);
        $pos1 = cSecurity::toInteger($pos1);
        $pos2 = cSecurity::toInteger($pos2);

        $this->select("idworkflowitem = $idworkflowitem AND position = " . $pos1);
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos1ID = $item->getField("idusersequence");

        $this->select("idworkflowitem = $idworkflowitem AND position = " . $pos2);
        if (($item = $this->next()) === false) {
            $this->lasterror(i18n("Swapping items failed: Item doesn't exist", "workflow"));
            return false;
        }

        $pos2ID = $item->getField("idusersequence");

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
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowUserSequence extends Item {

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('workflow_user_sequences'), "idusersequence");
    }

    /**
     * Override setField Function to prevent that somebody modifies
     * idsequence.
     *
     * @param string $field Field to set
     * @param string $value Value to set
     * @param bool   $safe
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setField($field, $value, $safe = true) {
        $idusersquence = false;
        switch ($field) {
            case "idworkflowitem":
                throw new cInvalidArgumentException("Please use create to modify idsequence. Direct modifications are not allowed");
            case "idusersequence":
                throw new cInvalidArgumentException("Please use create to modify idsequence. Direct modifications are not allowed");
            case "position":
                throw new cInvalidArgumentException("Please use create and swap to set the position. Direct modifications are not allowed");
            case "iduser":
                if ($value != 0) {
                    $db = cRegistry::getDb();

                    $sql = "SELECT `user_id` FROM `%s` WHERE `user_id` = '%s'";
                    $db->query($sql, cRegistry::getDbTableName('user'), $value);
                    if (!$db->nextRecord()) {
                        $sql = "SELECT `group_id` FROM `%s` WHERE `group_id` = '%s'";
                        $db->query($sql, cRegistry::getDbTableName('groups'), $value);
                        if (!$db->nextRecord()) {
                            $this->lasterror = i18n("Can't set user_id: User or group doesn't exist", "workflow");
                            return false;
                        }
                    }
                    $idusersquence = parent::getField('idusersequence');
                }
        }

        $result = parent::setField($field, $value, $safe);
        if ($idusersquence) {
            $workflowUserSequences = new WorkflowUserSequences();
            $workflowUserSequences->updateArtAllocation(0);
        }

        return $result;
    }

    /**
     * Returns the associated workflowItem for this user sequence
     *
     * @return bool|WorkflowItem
     * @throws cDbException
     * @throws cException
     */
    public function getWorkflowItem() {
        if ($this->isLoaded()) {
            $workflowItem = new WorkflowItem();
            $workflowItem->loadByPrimaryKey($this->values["idworkflowitem"]);
            return ($workflowItem);
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
    public function setWorkflowItem($value) {
        parent::setField("idworkflowitem", cSecurity::toInteger($value));
    }

    /**
     * Interface to set position.
     * Should only be called by "create".
     *
     * @param int $value The value to set
     */
    public function setPosition($value) {
        parent::setField("position", cSecurity::toInteger($value));
    }

}
