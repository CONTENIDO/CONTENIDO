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
     * @param string $groupname Specifies the groupname
     * @param int $defaultgroup Specifies, if group is default group (optional)
     * @return NewsletterRecipientGroup
     * @throws cException
     */
    public function create($groupname, $defaultgroup = 0)
    {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $group = new NewsletterRecipientGroup();

        // _arrInFilters = ['urlencode', 'htmlspecialchars', 'addslashes'];

        $mangledGroupName = $group->inFilter($groupname);
        $this->setWhere('idclient', $client);
        $this->setWhere('idlang', $lang);
        $this->setWhere('groupname', $mangledGroupName);
        $this->query();

        if ($this->next()) {
            // Groupname exists, append random hash
            $groupname = $groupname . md5(rand());
        }

        $item = $this->createNewItem();
        $item->set('idclient', $client);
        $item->set('idlang', $lang);
        $item->set('groupname', $groupname);
        $item->set('defaultgroup', $defaultgroup);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from group member table before deleting group
     *
     * @inheritDoc
     * @param $id int specifies the newsletter recipient group
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
     * @param mixed $id Specifies the ID of item to load
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
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        if ($this->get('defaultgroup') == 1) {
            $oItems = new NewsletterRecipientGroupCollection();
            $oItems->setWhere('idclient', $client);
            $oItems->setWhere('idlang', $lang);
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
     * @param int $idrecipientgroup specifies the newsletter group
     * @param int $idrecipient specifies the newsletter user
     * @return NewsletterRecipientGroupMember|false
     * @throws cDbException|cException
     */
    public function create($idrecipientgroup, $idrecipient)
    {
        $this->setWhere('idnewsgroup', $idrecipientgroup);
        $this->setWhere('idnewsrcp', $idrecipient);
        $this->query();

        if ($this->next()) {
            return false;
        }

        $oItem = $this->createNewItem();

        $oItem->set('idnewsrcp', $idrecipient);
        $oItem->set('idnewsgroup', $idrecipientgroup);
        $oItem->store();

        return $oItem;
    }

    /**
     * Removes an association
     *
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $idrecipient      int specifies the newsletter user
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function remove($idrecipientgroup, $idrecipient)
    {
        $idrecipientgroup = cSecurity::toInteger($idrecipientgroup);
        $idrecipient = cSecurity::toInteger($idrecipient);

        $this->setWhere('idnewsgroup', $idrecipientgroup);
        $this->setWhere('idnewsrcp', $idrecipient);
        $this->query();

        if ($oItem = $this->next()) {
            $this->delete($oItem->get('idnewsgroupmember'));
        }
    }

    /**
     * Removes all associations from any newsletter group
     *
     * @param $idrecipient int specifies the newsletter recipient
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removeRecipientFromGroups($idrecipient)
    {
        $this->setWhere('idnewsrcp', cSecurity::toInteger($idrecipient));
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get('idnewsgroupmember'));
        }
    }

    /**
     * Removes all associations of a newsletter group
     *
     * @param $idgroup int specifies the newsletter recipient group
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removeGroup($idgroup)
    {
        $this->setWhere('idnewsgroup', cSecurity::toInteger($idgroup));
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get('idnewsgroupmember'));
        }
    }

    /**
     * Returns all recipients in a single group
     *
     * @param int $idrecipientgroup specifies the newsletter group
     * @param bool $asObjects specifies if the function should return objects
     * @return int[]|NewsletterRecipient[] RecipientRecipient items or list of ids
     * @throws cDbException|cException
     */
    public function getRecipientsInGroup($idrecipientgroup, $asObjects = true): array
    {
        $this->setWhere('idnewsgroup', cSecurity::toInteger($idrecipientgroup));
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
     * @param mixed $id Specifies the ID of item to load
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
            case 'idnewsrcp':
            case 'idnewsgroup':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
