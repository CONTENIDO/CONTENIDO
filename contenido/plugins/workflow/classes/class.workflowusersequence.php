<?php
/**
 * This file contains the class for workflow user sequence managements.
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
 * Class for workflow user sequence management.
 *
 * @package Plugin
 * @subpackage Workflow
 */
class WorkflowUserSequences extends ItemCollection {

    /**
     * Constructor Function
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow_user_sequences"], "idusersequence");
        $this->_setItemClass("WorkflowUserSequence");
    }

    public function delete($id) {
        global $cfg, $idworkflow;

        $item = new WorkflowUserSequence();
        $item->loadByPrimaryKey($id);

        $pos = $item->get("position");
        $idworkflowitem = $item->get("idworkflowitem");
        $this->select("position > $pos AND idworkflowitem = " . (int) $idworkflowitem);
        while (($obj = $this->next()) !== false) {
            $pos = $obj->get("position") - 1;
            $obj->setPosition($pos);
            $obj->store();
        }

        parent::delete($id);

        $this->updateArtAllocation($id);
    }

    public function updateArtAllocation($idusersequence) {
        global $idworkflow, $cfg;
        $oDb = cRegistry::getDb();

        $aIdArtLang = array();
        $sSql = 'SELECT idartlang FROM ' . $cfg["tab"]["workflow_art_allocation"] . ' WHERE idusersequence = ' . $oDb->escape($idusersequence) . ';';
        $oDb->query($sSql);
        while ($oDb->nextRecord()) {
            array_push($aIdArtLang, $oDb->f('idartlang'));
        }

        $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_art_allocation"] . ' WHERE idusersequence = ' . $oDb->escape($idusersequence) . ';';
        $oDb->query($sSql);

        foreach ($aIdArtLang as $iIdArtLang) {
            setUserSequence($iIdArtLang, $idworkflow);
        }
    }

    public function create($idworkflowitem) {
        global $auth, $client, $idworkflow;

        $workflowitems = new WorkflowItems();
        if (!$workflowitems->exists($idworkflowitem)) {
            $this->lasterror = i18n("Workflow item doesn't exist. Can't create entry.", "workflow");
            return false;
        }

        $this->select("idworkflowitem = " . (int) $idworkflowitem, "", "position DESC", "1");

        $item = $this->next();

        if ($item === false) {
            $lastPos = 1;
        } else {
            $lastPos = $item->getField("position") + 1;
        }

        $newitem = $this->createNewItem();
        $newitem->setWorkflowItem($idworkflowitem);
        $newitem->setPosition($lastPos);
        $newitem->store();

        return $newitem;
    }

    public function swap($idworkflowitem, $pos1, $pos2) {
        $this->select("idworkflowitem = '$idworkflowitem' AND position = " . (int) $pos1);
        if (($item = $this->next()) === false) {
            $this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
            return false;
        }

        $pos1ID = $item->getField("idusersequence");

        $this->select("idworkflowitem = '$idworkflowitem' AND position = " . (int) $pos2);
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
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow_user_sequences"], "idusersequence");
    }

    /**
     * Override setField Function to prevent that somebody modifies
     * idsequence.
     *
     * @param string $field Field to set
     * @param string $value Value to set
     * @param bool $safe
     * @throws cInvalidArgumentException if the field is idworkflowitem,
     *         idusersequence or position
     */
    public function setField($field, $value, $safe = true) {
        global $cfg;

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
                    $sql = "SELECT user_id FROM " . $cfg['tab']['user'] . " WHERE user_id = '" . $db->escape($value) . "'";
                    $db->query($sql);

                    if (!$db->nextRecord()) {
                        $sql = "SELECT group_id FROM " . $cfg["tab"]["groups"] . " WHERE group_id = '" . $db->escape($value) . "'";

                        $db->query($sql);
                        if (!$db->nextRecord()) {
                            $this->lasterror = i18n("Can't set user_id: User or group doesn't exist", "workflow");
                            return false;
                        }
                    }
                    $idusersquence = parent::getField('idusersequence');
                }
        }

        parent::setField($field, $value, $safe);
        if ($idusersquence) {
            $workflowUserSequences = new WorkflowUserSequences();
            $workflowUserSequences->updateArtAllocation(0);
        }
    }

    /**
     * Returns the associated workflowItem for this user sequence
     *
     * @param none
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
     * @param string $value The value to set
     */
    public function setWorkflowItem($value) {
        parent::setField("idworkflowitem", $value);
    }

    /**
     * Interface to set idworkflowitem.
     * Should only be called by "create".
     *
     * @param string $value The value to set
     */
    public function setPosition($value) {
        parent::setField("position", $value);
    }

}

?>