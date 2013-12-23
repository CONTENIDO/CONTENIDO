<?php
/**
 * This file contains the Workflow management class.
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

$cfg["tab"]["workflow"] = $cfg['sql']['sqlprefix'] . "_piwf_workflow";
$cfg["tab"]["workflow_allocation"] = $cfg['sql']['sqlprefix'] . "_piwf_allocation";
$cfg["tab"]["workflow_art_allocation"] = $cfg['sql']['sqlprefix'] . "_piwf_art_allocation";
$cfg["tab"]["workflow_items"] = $cfg['sql']['sqlprefix'] . "_piwf_items";
$cfg["tab"]["workflow_tasks"] = $cfg['sql']['sqlprefix'] . "_piwf_tasks";
$cfg["tab"]["workflow_user_sequences"] = $cfg['sql']['sqlprefix'] . "_piwf_user_sequences";
$cfg["tab"]["workflow_actions"] = $cfg['sql']['sqlprefix'] . "_piwf_actions";

plugin_include('workflow', 'classes/class.workflowactions.php');
plugin_include('workflow', 'classes/class.workflowallocation.php');
plugin_include('workflow', 'classes/class.workflowartallocation.php');
plugin_include('workflow', 'classes/class.workflowitems.php');
plugin_include('workflow', 'classes/class.workflowusersequence.php');

/**
 * Workflow management class.
 *
 * @package Plugin
 * @subpackage Workflow
 */
class Workflows extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["workflow"], "idworkflow");
        $this->_setItemClass("Workflow");
    }

    public function create() {
        global $auth, $client, $lang;
        $newitem = parent::createNewItem();
        $newitem->setField("created", date('Y-m-d H:i:s'));
        $newitem->setField("idauthor", $auth->auth["uid"]);
        $newitem->setField("idclient", $client);
        $newitem->setField("idlang", $lang);
        $newitem->store();

        return $newitem;
    }

    /**
     * Deletes all corresponding informations to this workflow and delegate call
     * to parent
     *
     * @param int $idWorkflow - id of workflow to delete
     */
    public function delete($idWorkflow) {
        global $cfg;
        $oDb = cRegistry::getDb();

        $aItemIdsDelete = array();
        $sSql = 'SELECT idworkflowitem FROM ' . $cfg["tab"]["workflow_items"] . ' WHERE idworkflow = ' . cSecurity::toInteger($idWorkflow) . ';';
        $oDb->query($sSql);
        while ($oDb->nextRecord()) {
            $aItemIdsDelete[] = (int) $oDb->f('idworkflowitem');
        }

        if (!empty($aItemIdsDelete)) {
            $aUserSequencesDelete = array();
            $sSql = 'SELECT idusersequence FROM ' . $cfg["tab"]["workflow_user_sequences"] . ' WHERE idworkflowitem in (' . implode(',', $aItemIdsDelete) . ');';
            $oDb->query($sSql);
            while ($oDb->nextRecord()) {
                $aUserSequencesDelete[] = (int) $oDb->f('idusersequence');
            }

            $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_user_sequences"] . ' WHERE idworkflowitem in (' . implode(',', $aItemIdsDelete) . ');';
            $oDb->query($sSql);

            $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_actions"] . ' WHERE idworkflowitem in (' . implode(',', $aItemIdsDelete) . ');';
            $oDb->query($sSql);
        }

        if (!empty($aUserSequencesDelete)) {
            $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_art_allocation"] . ' WHERE idusersequence in (' . implode(',', $aUserSequencesDelete) . ');';
            $oDb->query($sSql);
        }

        $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_items"] . ' WHERE idworkflow = ' . cSecurity::toInteger($idWorkflow) . ';';
        $oDb->query($sSql);

        $sSql = 'DELETE FROM ' . $cfg["tab"]["workflow_allocation"] . ' WHERE idworkflow = ' . cSecurity::toInteger($idWorkflow) . ';';
        $oDb->query($sSql);

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
     *
     * @param string $table The table to use as information source
     */
    public function __construct() {
        global $cfg;

        parent::__construct($cfg["tab"]["workflow"], "idworkflow");
    }

}

/* Helper functions */
function getWorkflowForCat($idcat) {
    global $lang;

    $idcatlang = getCatLang($idcat, $lang);
    if (!$idcatlang) {
        return 0;
    }
    $workflows = new WorkflowAllocations();
    $workflows->select('idcatlang = ' . (int) $idcatlang);
    if (($obj = $workflows->next()) !== false) {
        // Sanity: Check if the workflow still exists
        $workflow = new Workflow();
        $res = $workflow->loadByPrimaryKey($obj->get('idworkflow'));
        return ($res == true) ? $obj->get('idworkflow') : 0;
    }
}

function getCatLang($idcat, $idlang) {
    // Get the idcatlang
    $oCatLangColl = new cApiCategoryLanguageCollection();
    $aIds = $oCatLangColl->getIdsByWhereClause('idlang = ' . (int) $idlang . ' AND idcat = ' . (int) $idcat);
    return (count($aIds) > 0) ? $aIds[0] : 0;
}

?>