<?php
/**
 * This file contains the class for workflow task collections.
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
 * Class for workflow task collections.
 *
 * @package Plugin
 * @subpackage Workflow
 * @method WorkflowTask createNewItem
 * @method WorkflowTask next
 */
class WorkflowTasks extends ItemCollection {
    /**
     * WorkflowTasks constructor.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["tasks"], "idtask");
        $this->_setItemClass("WorkflowTask");
    }

    /**
     * @return WorkflowTask
     */
    public function create() {
        return $this->createNewItem();
    }

    /**
     * @param string $where
     * @param string $group_by
     * @param string $order_by
     * @param string $limit
     *
     * @return bool
     */
    public function select($where = "", $group_by = "", $order_by = "", $limit = "") {
        global $client;

        if ($where != "") {
            $where = $where . " AND idclient = " . cSecurity::toInteger($client);
        }
        return parent::select($where, $group_by, $order_by, $limit);
    }

}

/**
 * Class WorkflowTask
 * Class for a single workflow task item
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowTask extends Item {
    /**
     * WorkflowTask constructor.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["tasks"], "idtask");
    }

}
