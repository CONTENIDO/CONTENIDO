<?php

/**
 * This file contains the Workflow management class.
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
 * Workflow management class.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @extends ItemCollection<Workflow>
 */
class Workflows extends ItemCollection
{

    use cItemCollectionIdsByClientIdAndLanguageIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow'), 'idworkflow');
        $this->_setItemClass('Workflow');
    }

    /**
     * @return Workflow
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function create()
    {
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $newItem = $this->createNewItem();
        $newItem->setField('created', date('Y-m-d H:i:s'));
        $newItem->setField('idauthor', $auth->getUserId());
        $newItem->setField('idclient', $client);
        $newItem->setField('idlang', $lang);
        $newItem->store();

        return $newItem;
    }

    /**
     * Deletes all corresponding information to this workflow and delegate call to parent
     *
     * @inheritDoc
     * @param int $id The workflow id.
     * @throws cDbException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $db = cRegistry::getDb();

        $itemIdsToDelete = [];
        $db->query(
            'SELECT `idworkflowitem` FROM `%s` WHERE `idworkflow` = %d',
            cDb::getTableName('workflow_items'),
            $id
        );
        while ($db->nextRecord()) {
            $itemIdsToDelete[] = cSecurity::toInteger($db->f('idworkflowitem'));
        }

        $userSequencesToDelete = [];
        if (!empty($itemIdsToDelete)) {
            $itemIdsToDelete = implode(',', $itemIdsToDelete);
            $db->query(
                'SELECT `idusersequence` FROM `%s` WHERE `idworkflowitem` IN (' . $itemIdsToDelete . ');',
                cDb::getTableName('workflow_user_sequences')
            );
            while ($db->nextRecord()) {
                $userSequencesToDelete[] = cSecurity::toInteger($db->f('idusersequence'));
            }

            $db->query(
                'DELETE FROM `%s` WHERE `idworkflowitem` IN (' . $itemIdsToDelete . ');',
                cDb::getTableName('workflow_user_sequences')
            );

            $db->query(
                'DELETE FROM `%s` WHERE `idworkflowitem` IN (' . $itemIdsToDelete . ');',
                cDb::getTableName('workflow_actions')
            );
        }

        if (!empty($userSequencesToDelete)) {
            $db->query(
                'DELETE FROM `%s` WHERE `idusersequence` IN (' . $itemIdsToDelete . ');',
                cDb::getTableName('workflow_art_allocation')
            );
        }

        $db->query(
            'DELETE FROM `%s` WHERE `idworkflow` = %d',
            cDb::getTableName('workflow_items'),
            $id
        );

        $db->query(
            'DELETE FROM `%s` WHERE `idworkflow` = %d',
            cDb::getTableName('workflow_allocation'),
            $id
        );

        return parent::delete($id);
    }

}

/**
 * Class Workflow
 * Class for a single workflow item
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class Workflow extends Item
{

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('workflow'), 'idworkflow');
    }

}

