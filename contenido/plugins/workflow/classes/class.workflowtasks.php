<?php

/**
 * This file contains the class for workflow task collections.
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
 * Class for workflow task collections.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @extends ItemCollection<WorkflowTask>
 */
class WorkflowTasks extends ItemCollection
{
    /**
     * WorkflowTasks constructor.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('tasks'), 'idtask');
        $this->_setItemClass('WorkflowTask');
    }

    /**
     * @return WorkflowTask
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create()
    {
        return $this->createNewItem();
    }

    /**
     * Extends the where statement. See the original function for the parameters.
     *
     * @inheritDoc
     */
    public function select($where = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        if ($where != '') {
            $where = $where . ' AND `idclient` = ' . cRegistry::getClientId();
        }

        return parent::select($where, $groupBy, $orderBy, $limit);
    }

}

/**
 * Class WorkflowTask
 * Class for a single workflow task item
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class WorkflowTask extends Item
{

    /**
     * WorkflowTask constructor.
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('tasks'), "idtask");
    }

}
