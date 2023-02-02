<?php
/**
 * This file contains the Workflow management class.
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
 * Workflow management class.
 *
 * @package Plugin
 * @subpackage Workflow
 * @method Workflow createNewItem
 * @method Workflow|bool next
 */
class Workflows extends ItemCollection {

    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('workflow'), "idworkflow");
        $this->_setItemClass("Workflow");
    }

    /**
     * @return Workflow
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function create() {
        $auth = cRegistry::getAuth();
        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        $newItem = $this->createNewItem();
        $newItem->setField("created", date('Y-m-d H:i:s'));
        $newItem->setField("idauthor", $auth->auth["uid"]);
        $newItem->setField("idclient", $client);
        $newItem->setField("idlang", $lang);
        $newItem->store();

        return $newItem;
    }

    /**
     * Deletes all corresponding information to this workflow and delegate call
     * to parent
     *
     * @param int $idWorkflow - id of workflow to delete
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function delete($idWorkflow) {
        $oDb = cRegistry::getDb();

        $aItemIdsDelete = [];
        $sSql = 'SELECT `idworkflowitem` FROM `%s` WHERE `idworkflow` = %d';
        $oDb->query($sSql, cRegistry::getDbTableName('workflow_items'), $idWorkflow);
        while ($oDb->nextRecord()) {
            $aItemIdsDelete[] = cSecurity::toInteger($oDb->f('idworkflowitem'));
        }

        if (!empty($aItemIdsDelete)) {
            $aUserSequencesDelete = [];
            $sSql = 'SELECT `idusersequence` FROM `%s` WHERE `idworkflowitem` IN (' . implode(',', $aItemIdsDelete) . ');';
            $oDb->query($sSql, cRegistry::getDbTableName('workflow_user_sequences'));
            while ($oDb->nextRecord()) {
                $aUserSequencesDelete[] = cSecurity::toInteger($oDb->f('idusersequence'));
            }

            $sSql = 'DELETE FROM `%s` WHERE `idworkflowitem` IN (' . implode(',', $aItemIdsDelete) . ');';
            $oDb->query($sSql, cRegistry::getDbTableName('workflow_user_sequences'));

            $sSql = 'DELETE FROM `%s` WHERE `idworkflowitem` IN (' . implode(',', $aItemIdsDelete) . ');';
            $oDb->query($sSql, cRegistry::getDbTableName('workflow_actions'));
        }

        if (!empty($aUserSequencesDelete)) {
            $sSql = 'DELETE FROM `%s` WHERE `idusersequence` IN (' . implode(',', $aUserSequencesDelete) . ');';
            $oDb->query($sSql, cRegistry::getDbTableName('workflow_art_allocation'));
        }

        $sSql = 'DELETE FROM `%s` WHERE `idworkflow` = %d';
        $oDb->query($sSql, cRegistry::getDbTableName('workflow_items'), $idWorkflow);

        $sSql = 'DELETE FROM `%s` WHERE `idworkflow` = %d';
        $oDb->query($sSql, cRegistry::getDbTableName('workflow_allocation'), $idWorkflow);

        parent::delete($idWorkflow);
    }

}

/**
 * Class Workflow
 * Class for a single workflow item
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Workflow extends Item {

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('workflow'), "idworkflow");
    }

}

