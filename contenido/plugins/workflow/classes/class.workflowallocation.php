<?php
/**
 * This file contains the class for workflow allocation management.
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
 * Class for workflow allocation management.
 *
 * @package Plugin
 * @subpackage Workflow
 * @method WorkflowAllocation createNewItem
 * @method WorkflowAllocation|bool next
 */
class WorkflowAllocations extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('workflow_allocation'), "idallocation");
        $this->_setItemClass("WorkflowAllocation");
    }

    /**
     * @param mixed $idallocation
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($idallocation) {
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        $obj = new WorkflowAllocation();
        $obj->loadByPrimaryKey($idallocation);

        $idcatlang = $obj->get("idcatlang");

        $db = cRegistry::getDb();
        $sql = "SELECT `idcat` FROM `%s` WHERE `idcatlang` = %d";
        $db->query($sql, cRegistry::getDbTableName('cat_lang'), $idcatlang);
        $db->nextRecord();
        $idcat = cSecurity::toInteger($db->f("idcat"));

        $sql = "SELECT `idart` FROM `%s` WHERE `idcat` = %d";
        $db->query($sql, cRegistry::getDbTableName('cat_art'), $idcat);

        $idArts = [];
        while ($db->nextRecord()) {
            $idArts[] = cSecurity::toInteger($db->f("idart"));
        }

        $idArtLangs = [];
        foreach ($idArts as $idart) {
            $sql = "SELECT `idartlang` FROM `%s` WHERE `idart` = %d AND `idlang` = %d";
            $db->query($sql, cRegistry::getDbTableName('art_lang'), $idart, $lang);
            if ($db->nextRecord()) {
                $idArtLangs[] = cSecurity::toInteger($db->f("idartlang"));
            }
        }

        $workflowArtAllocation = new WorkflowArtAllocation();
        $workflowArtAllocations = new WorkflowArtAllocations();

        foreach ($idArtLangs as $idartlang) {
            $workflowArtAllocation->loadBy("idartlang", $idartlang);
            $workflowArtAllocations->delete($workflowArtAllocation->get("idartallocation"));
        }

        return parent::delete($idallocation);
    }

    /**
     * @param $idworkflow
     * @param $idcatlang
     *
     * @return bool|Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idworkflow, $idcatlang) {
        $idworkflow = cSecurity::toInteger($idworkflow);
        $idcatlang = cSecurity::toInteger($idcatlang);

        $this->select("idcatlang = $idcatlang");

        if ($this->next() !== false) {
            $this->lasterror = i18n("Category already has a workflow assigned", "workflow");
            return false;
        }

        $workflows = new Workflows();
        $workflows->select("idworkflow = $idworkflow");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        $newItem = $this->createNewItem();
        if (!$newItem->setWorkflow($idworkflow)) {
            $this->lasterror = $newItem->lasterror;
            $workflows->delete($newItem->getField("idallocation"));
            return false;
        }

        if (!$newItem->setCatLang($idcatlang)) {
            $this->lasterror = $newItem->lasterror;
            $workflows->delete($newItem->getField("idallocation"));
            return false;
        }

        $newItem->store();

        return $newItem;
    }

}

/**
 * Class WorkflowAllocation
 * Class for a single workflow allocation item
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowAllocation extends Item {

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('workflow_allocation'), "idallocation");
    }

    /**
     * Overridden setField function.
     * Users should only use setWorkflow.
     *
     * @param string $field Void field since we override the usual setField
     *                      function
     * @param string $value Void field since we override the usual setField
     *                      function
     * @param bool   $safe
     *
     * @throws cBadMethodCallException if this function is called
     */
    public function setField($field, $value, $safe = true) {
        throw new cBadMethodCallException("Don't use setField for WorkflowAllocation items! Use setWorkflow instead!");
    }

    /**
     * setWorkflow sets the workflow for the current item.
     *
     * @param int $idworkflow Workflow-ID to set the item to
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setWorkflow($idworkflow) {
        $workflows = new Workflows();

        $workflows->select("idworkflow = '$idworkflow'");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        parent::setField("idworkflow", $idworkflow);
        $this->store();
        return true;
    }

    /**
     * setCatLang sets the idcatlang for the current item.
     * Should
     * only be called by the create function.
     *
     * @param int $idcatlang idcatlang to set
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setCatLang($idcatlang) {
        $idcatlang = cSecurity::toInteger($idcatlang);

        $allocations = new WorkflowAllocations();

        $allocations->select("idcatlang = $idcatlang");

        if ($allocations->next() !== false) {
            $this->lasterror = i18n("Category already has a workflow assigned", "workflow");
            return false;
        }

        $db = cRegistry::getDb();
        $sql = "SELECT `idcatlang` FROM `%s` WHERE `idcatlang` = %d";
        $db->query($sql,  cRegistry::getDbTableName('cat_lang'), $idcatlang);

        if (!$db->nextRecord()) {
            $this->lasterror = i18n("Category doesn't exist, assignment failed", "workflow");
            return false;
        }

        parent::setField("idcatlang", $idcatlang);
        $this->store();
        return true;
    }

}
