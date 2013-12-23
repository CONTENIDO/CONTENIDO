<?php
/**
 * This file contains the class for workflow allocation management.
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
 * Class for workflow allocation management.
 *
 * @package Plugin
 * @subpackage Workflow
 */
class WorkflowAllocations extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow_allocation"], "idallocation");
        $this->_setItemClass("WorkflowAllocation");
    }

    public function delete($idallocation) {
        global $cfg, $lang;

        $obj = new WorkflowAllocation();
        $obj->loadByPrimaryKey($idallocation);

        $idcatlang = $obj->get("idcatlang");

        $db = cRegistry::getDb();
        $sql = "SELECT idcat FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcatlang = '" . cSecurity::toInteger($idcatlang) . "'";
        $db->query($sql);
        $db->nextRecord();
        $idcat = $db->f("idcat");

        $sql = "SELECT idart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat = '" . cSecurity::toInteger($idcat) . "'";
        $db->query($sql);

        while ($db->nextRecord()) {
            $idarts[] = $db->f("idart");
        }

        $idartlangs = array();

        if (is_array($idarts)) {
            foreach ($idarts as $idart) {
                $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = '" . cSecurity::toInteger($idart) . "' and idlang = '" . cSecurity::toInteger($lang) . "'";
                $db->query($sql);
                if ($db->nextRecord()) {
                    $idartlangs[] = $db->f("idartlang");
                }
            }
        }

        $workflowArtAllocation = new WorkflowArtAllocation();
        $workflowArtAllocations = new WorkflowArtAllocations();

        foreach ($idartlangs as $idartlang) {
            $workflowArtAllocation->loadBy("idartlang", $idartlang);
            $workflowArtAllocations->delete($workflowArtAllocation->get("idartallocation"));
        }

        parent::delete($idallocation);
    }

    public function create($idworkflow, $idcatlang) {
        $this->select("idcatlang = '$idcatlang'");

        if ($this->next() !== false) {
            $this->lasterror = i18n("Category already has a workflow assigned", "workflow");
            return false;
        }

        $workflows = new Workflows();
        $workflows->select("idworkflow = '$idworkflow'");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }
        $newitem = parent::createNewItem();
        if (!$newitem->setWorkflow($idworkflow)) {
            $this->lasterror = $newitem->lasterror;
            $workflows->delete($newitem->getField("idallocation"));
            return false;
        }

        if (!$newitem->setCatLang($idcatlang)) {
            $this->lasterror = $newitem->lasterror;
            $workflows->delete($newitem->getField("idallocation"));
            return false;
        }

        $newitem->store();

        return ($newitem);
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
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;

        parent::__construct($cfg["tab"]["workflow_allocation"], "idallocation");
    }

    /**
     * Overridden setField function.
     * Users should only use setWorkflow.
     *
     * @param string $field Void field since we override the usual setField
     *            function
     * @param string $value Void field since we override the usual setField
     *            function
     * @throws cBadMethodCallException if this function is called
     */
    public function setField($field, $value, $safe = true) {
        throw new cBadMethodCallException("Don't use setField for WorkflowAllocation items! Use setWorkflow instead!");
    }

    /**
     * setWorkflow sets the workflow for the current item.
     *
     * @param int $idworkflow Workflow-ID to set the item to
     */
    public function setWorkflow($idworkflow) {
        $workflows = new Workflows();

        $workflows->select("idworkflow = '$idworkflow'");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        parent::setField("idworkflow", $idworkflow);
        parent::store();
        return true;
    }

    /**
     * setCatLang sets the idcatlang for the current item.
     * Should
     * only be called by the create function.
     *
     * @param int $idcatlang idcatlang to set.
     */
    public function setCatLang($idcatlang) {
        global $cfg;

        $allocations = new WorkflowAllocations();

        $allocations->select("idcatlang = '$idcatlang'");

        if ($allocations->next() !== false) {
            $this->lasterror = i18n("Category already has a workflow assigned", "workflow");
            return false;
        }

        $db = cRegistry::getDb();
        $sql = "SELECT idcatlang FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcatlang = '" . cSecurity::toInteger($idcatlang) . "'";
        $db->query($sql);

        if (!$db->nextRecord()) {
            $this->lasterror = i18n("Category doesn't exist, assignment failed", "workflow");
            return false;
        }

        parent::setField("idcatlang", $idcatlang);
        parent::store();
        return true;
    }

}

?>