<?php

/**
 * This file contains the Recipient groups class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Recipient group management class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @extends ItemCollection<NewsletterRecipientGroup>
 */
class NewsletterRecipientGroupCollection extends ItemCollection
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
        parent::__construct(cDb::getTableName('news_groups'), 'idnewsgroup');
        $this->_setItemClass('NewsletterRecipientGroup');
    }

    /**
     * Creates a new group
     *
     * @param string $groupName The group name
     * @param int $defaultGroup Specifies, if group is default group (optional)
     * @return NewsletterRecipientGroup
     * @throws cException
     */
    public function create($groupName, $defaultGroup = 0)
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();
        $group = new NewsletterRecipientGroup();

        // _arrInFilters = ['urlencode', 'htmlspecialchars', 'addslashes'];

        $mangledGroupName = $group->inFilter($groupName);
        $this->setWhere('idclient', $clientId);
        $this->setWhere('idlang', $languageId);
        $this->setWhere('groupname', $mangledGroupName);
        $this->query();

        if ($this->next()) {
            // Groupname exists, append random hash
            $groupName = $groupName . md5(rand());
        }

        $item = $this->createNewItem();
        $item->set('idclient', $clientId);
        $item->set('idlang', $languageId);
        $item->set('groupname', $groupName);
        $item->set('defaultgroup', $defaultGroup);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from group member table before deleting group
     *
     * @inheritDoc
     * @param $id int The newsletter recipient group id.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        $oAssociations = new NewsletterRecipientGroupMemberCollection();
        $oAssociations->setWhere('idnewsgroup', $id);
        $oAssociations->query();
        while ($oItem = $oAssociations->next()) {
            $oAssociations->delete($oItem->get('idnewsgroupmember'));
        }

        return parent::delete($id);
    }

}

/**
 * Single RecipientGroup Item
 */
class NewsletterRecipientGroup extends Item
{
    /**
     * Constructor Function
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('news_groups'), 'idnewsgroup');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Overridden store() method to ensure, that there is only one default group
     *
     * @inheritDoc
     * @throws cException
     */
    public function store()
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        if ($this->get('defaultgroup') == 1) {
            $oItems = new NewsletterRecipientGroupCollection();
            $oItems->setWhere('idclient', $clientId);
            $oItems->setWhere('idlang', $languageId);
            $oItems->setWhere('defaultgroup', 1);
            $oItems->setWhere('idnewsgroup', $this->get('idnewsgroup'), "<>");
            $oItems->query();

            while ($oItem = $oItems->next()) {
                $oItem->set('defaultgroup', 0);
                $oItem->store();
            }
        }
        return parent::store();
    }

    /**
     * User-defined setter for newsletter recipient group fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idlang':
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}

/**
 * Recipient group member management class
 *
 * @extends ItemCollection<NewsletterRecipientGroupMember>
 */
class NewsletterRecipientGroupMemberCollection extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('news_groupmembers'), 'idnewsgroupmember');
        $this->_setJoinPartner('NewsletterRecipientGroupCollection');
        $this->_setJoinPartner('NewsletterRecipientCollection');
        $this->_setItemClass('NewsletterRecipientGroupMember');
    }

    /**
     * Creates a new association
     *
     * @param int $recipientGroupId specifies the newsletter group
     * @param int $recipientId specifies the newsletter user
     * @return NewsletterRecipientGroupMember|false
     * @throws cDbException|cException
     */
    public function create($recipientGroupId, $recipientId)
    {
        $this->setWhere('idnewsgroup', $recipientGroupId);
        $this->setWhere('idnewsrcp', $recipientId);
        $this->query();

        if ($this->next()) {
            return false;
        }

        $oItem = $this->createNewItem();

        $oItem->set('idnewsrcp', $recipientId);
        $oItem->set('idnewsgroup', $recipientGroupId);
        $oItem->store();

        return $oItem;
    }

    /**
     * Removes an association
     *
     * @param $recipientGroupId int specifies the newsletter group
     * @param $recipientId      int specifies the newsletter user
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function remove($recipientGroupId, $recipientId)
    {
        $recipientGroupId = cSecurity::toInteger($recipientGroupId);
        $recipientId = cSecurity::toInteger($recipientId);

        $this->setWhere('idnewsgroup', $recipientGroupId);
        $this->setWhere('idnewsrcp', $recipientId);
        $this->query();

        if ($oItem = $this->next()) {
            $this->delete($oItem->get('idnewsgroupmember'));
        }
    }

    /**
     * Removes all associations from any newsletter group
     *
     * @param $recipientId int specifies the newsletter recipient
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removeRecipientFromGroups($recipientId)
    {
        $this->setWhere('idnewsrcp', cSecurity::toInteger($recipientId));
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get('idnewsgroupmember'));
        }
    }

    /**
     * Removes all associations of a newsletter group
     *
     * @param $recipientGroupId int specifies the newsletter recipient group
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removeGroup($recipientGroupId)
    {
        $this->setWhere('idnewsgroup', cSecurity::toInteger($recipientGroupId));
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get('idnewsgroupmember'));
        }
    }

    /**
     * Returns all recipients in a single group
     *
     * @param int $recipientGroupId specifies the newsletter group
     * @param bool $asObjects specifies if the function should return objects
     * @return int[]|NewsletterRecipient[] RecipientRecipient items or list of ids
     * @throws cDbException|cException
     */
    public function getRecipientsInGroup($recipientGroupId, $asObjects = true): array
    {
        $this->setWhere('idnewsgroup', cSecurity::toInteger($recipientGroupId));
        $this->query();

        $aObjects = [];

        while ($oItem = $this->next()) {
            if ($asObjects) {
                $oRecipient = new NewsletterRecipient();
                $oRecipient->loadByPrimaryKey($oItem->get('idnewsrcp'));

                $aObjects[] = $oRecipient;
            } else {
                $aObjects[] = $oItem->get('idnewsrcp');
            }
        }

        return ($aObjects);
    }

}

/**
 * Single RecipientGroup Item
 */
class NewsletterRecipientGroupMember extends Item
{
    /**
     * Constructor Function
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('news_groupmembers'), 'idnewsgroupmember');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for newsletter recipient group member fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idnewsgroupmember':
            case 'idnewsrcp':
            case 'idnewsgroup':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * @inheritDoc
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'idnewsgroupmember':
            case 'idnewsrcp':
            case 'idnewsgroup':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

}
