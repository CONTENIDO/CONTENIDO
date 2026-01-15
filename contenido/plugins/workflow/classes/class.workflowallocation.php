<?php

/**
 * This file contains the class for workflow allocation management.
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
 * Class for workflow allocation management.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @extends ItemCollection<WorkflowAllocation>
 */
class WorkflowAllocations extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_allocation'), 'idallocation');
        $this->_setItemClass('WorkflowAllocation');
    }

    /**
     * @inheritDoc
     * @param int $id The workflow allocation id.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        $lang = cRegistry::getLanguageId();

        $obj = new WorkflowAllocation();
        $obj->loadByPrimaryKey($id);

        $categoryLanguageId = cSecurity::toInteger($obj->get('idcatlang'));

        $db = cRegistry::getDb();
        $db->query(
            "SELECT `idcat` FROM `%s` WHERE `idcatlang` = %d",
            cDb::getTableName('cat_lang'),
            $categoryLanguageId
        );
        $db->nextRecord();
        $categoryId = cSecurity::toInteger($db->f('idcat'));

        $db->query(
            "SELECT `idart` FROM `%s` WHERE `idcat` = %d",
            cDb::getTableName('cat_art'),
            $categoryId
        );
        $articleIds = [];
        while ($db->nextRecord()) {
            $articleIds[] = cSecurity::toInteger($db->f('idart'));
        }

        $articleLanguageIds = [];
        foreach ($articleIds as $idart) {
            $db->query(
                "SELECT `idartlang` FROM `%s` WHERE `idart` = %d AND `idlang` = %d",
                cDb::getTableName('art_lang'),
                $idart,
                $lang
            );
            if ($db->nextRecord()) {
                $articleLanguageIds[] = cSecurity::toInteger($db->f('idartlang'));
            }
        }

        $workflowArtAllocation = new WorkflowArtAllocation();
        $workflowArtAllocations = new WorkflowArtAllocations();

        foreach ($articleLanguageIds as $articleLanguageId) {
            $workflowArtAllocation->loadBy('idartlang', $articleLanguageId);
            $workflowArtAllocations->delete($workflowArtAllocation->get('idartallocation'));
        }

        return parent::delete($id);
    }

    /**
     * @param $workflowId
     * @param $categoryLanguageId
     * @return WorkflowAllocation|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($workflowId, $categoryLanguageId)
    {
        $workflowId = cSecurity::toInteger($workflowId);
        $categoryLanguageId = cSecurity::toInteger($categoryLanguageId);

        $this->select("`idcatlang` = $categoryLanguageId");

        if ($this->next() !== false) {
            $this->lasterror = i18n("Category already has a workflow assigned", "workflow");
            return false;
        }

        $workflows = new Workflows();
        $workflows->select("`idworkflow` = $workflowId");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        $newItem = $this->createNewItem();
        if (!$newItem->setWorkflow($workflowId)) {
            $this->lasterror = $newItem->lasterror;
            $workflows->delete($newItem->getField('idallocation'));
            return false;
        }

        if (!$newItem->setCatLang($categoryLanguageId)) {
            $this->lasterror = $newItem->lasterror;
            $workflows->delete($newItem->getField('idallocation'));
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
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class WorkflowAllocation extends Item
{

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow_allocation'), 'idallocation');
    }

    /**
     * Overridden setField function.
     * Users should only use setWorkflow.
     *
     * @inheritDoc
     * @throws cBadMethodCallException if this function is called
     */
    public function setField($name, $value, $safe = true)
    {
        throw new cBadMethodCallException(
            "Don't use setField for WorkflowAllocation items! Use setWorkflow instead!"
        );
    }

    /**
     * setWorkflow sets the workflow for the current item.
     *
     * @param int $workflowId Workflow-ID to set the item to
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setWorkflow($workflowId): bool
    {
        $workflows = new Workflows();

        $workflows->select("`idworkflow` = '$workflowId'");

        if ($workflows->next() === false) {
            $this->lasterror = i18n("Workflow doesn't exist", "workflow");
            return false;
        }

        parent::setField('idworkflow', $workflowId);
        $this->store();
        return true;
    }

    /**
     * setCatLang sets the category language id for the current item.
     * Should only be called by the create function.
     *
     * @param int $categoryLanguageId The category language id to set
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setCatLang($categoryLanguageId): bool
    {
        $categoryLanguageId = cSecurity::toInteger($categoryLanguageId);

        $allocations = new WorkflowAllocations();

        $allocations->select("`idcatlang` = $categoryLanguageId");

        if ($allocations->next() !== false) {
            $this->lasterror = i18n("Category already has a workflow assigned", "workflow");
            return false;
        }

        $db = cRegistry::getDb();
        $db->query(
            "SELECT `idcatlang` FROM `%s` WHERE `idcatlang` = %d",
            cDb::getTableName('cat_lang'),
            $categoryLanguageId
        );

        if (!$db->nextRecord()) {
            $this->lasterror = i18n("Category doesn't exist, assignment failed", "workflow");
            return false;
        }

        parent::setField('idcatlang', $categoryLanguageId);
        $this->store();
        return true;
    }

}
