<?php

/**
 * This file contains the Newsletter Collection class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 *
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Newsletter Collection class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @extends ItemCollection<NewsletterRecipient>
 */
class NewsletterRecipientCollection extends ItemCollection
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
        parent::__construct(cDb::getTableName('news_rcp'), 'idnewsrcp');
        $this->_setItemClass('NewsletterRecipient');
    }

    /**
     * Creates a new recipient
     *
     * @param string $email Specifies the e-mail address
     * @param string $name Specifies the recipient name (optional)
     * @param int $confirmed Specifies if the recipient is confirmed (optional)
     * @param string $joinId Specifies additional recipient group ids to join (optional, e.g. 47,12,...)
     * @param int $newsType Specifies the message type for the recipient (0 = text, 1 = html)
     * @return NewsletterRecipient
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($email, $name = '', $confirmed = 0, $joinId = '', $newsType = 0)
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();
        $auth = cRegistry::getAuth();

        // Check if the e-mail address already exists
        $email = cString::toLowerCase($email); // e-mail always lower case
        $this->setWhere('idclient', $clientId);
        $this->setWhere('idlang', $languageId);
        $this->setWhere('email', $email);
        $this->query();

        if ($this->next()) {
            // 0: Deactivate 'confirmed'
            return $this->create(
                $email . '_' . cString::getPartOfString(md5(rand()), 0, 10),
                $name,
                0,
                $joinId,
                $newsType
            );
        }
        $oItem = $this->createNewItem();
        $oItem->set('idclient', $clientId);
        $oItem->set('idlang', $languageId);
        $oItem->set('name', $name);
        $oItem->set('email', $email);
        // Generating UID, 30 characters
        $oItem->set('hash', cString::getPartOfString(md5(rand()), 0, 17) . uniqid());
        $oItem->set('confirmed', $confirmed);
        $oItem->set('news_type', $newsType);

        if ($confirmed) {
            $oItem->set('confirmeddate', date('Y-m-d H:i:s'), false);
        }
        $oItem->set('deactivated', 0);
        $oItem->set('created', date('Y-m-d H:i:s'), false);
        $oItem->set('author', $auth->getUserId());
        $oItem->store();

        // Getting internal id of new recipient
        $iIDRcp = $oItem->get('idnewsrcp');

        // Add this recipient to the default recipient group (if available)
        $oGroups = new NewsletterRecipientGroupCollection();
        $oGroupMembers = new NewsletterRecipientGroupMemberCollection();

        $oGroups->setWhere('idclient', $clientId);
        $oGroups->setWhere('idlang', $languageId);
        $oGroups->setWhere('defaultgroup', 1);
        $oGroups->query();

        while ($oGroup = $oGroups->next()) {
            $iIDGroup = $oGroup->get('idnewsgroup');
            $oGroupMembers->create($iIDGroup, $iIDRcp);
        }

        // Add to other recipient groups as well? Do so!
        if ($joinId != '') {
            $aJoinID = explode(",", $joinId);

            if (count($aJoinID) > 0) {
                foreach ($aJoinID as $iIDGroup) {
                    $oGroupMembers->create($iIDGroup, $iIDRcp);
                }
            }
        }

        return $oItem;
    }

    /**
     * Overridden delete method to remove recipient from group member table before deleting recipient
     *
     * @inheritDoc
     * @param int $id The newsletter recipient id.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        $oAssociations = new NewsletterRecipientGroupMemberCollection();
        $oAssociations->setWhere('idnewsrcp', $id);
        $oAssociations->query();
        while ($oItem = $oAssociations->next()) {
            $oAssociations->delete($oItem->get('idnewsgroupmember'));
        }

        return parent::delete($id);
    }

    /**
     * Purge method to delete recipients which hasn't been confirmed since over a month
     *
     * @param $timeframe int Days after creation a not confirmed recipient will be removed
     * @return int Count of deleted recipients
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function purge($timeframe)
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        $oRecipientCollection = new NewsletterRecipientCollection();

        // DATEDIFF(created, NOW()) > 30 would be better, but it's only
        // available in MySQL V4.1.1 and above
        // Note, that, TO_DAYS or NOW may not be available in other database
        // systems than MySQL
        $oRecipientCollection->setWhere('idclient', $clientId);
        $oRecipientCollection->setWhere('idlang', $languageId);
        $oRecipientCollection->setWhere('confirmed', 0);
        $oRecipientCollection->setWhere("(TO_DAYS(NOW()) - TO_DAYS(created))", $timeframe, ">");
        $oRecipientCollection->query();

        while ($oItem = $oRecipientCollection->next()) {
            $oRecipientCollection->delete($oItem->get('idnewsrcp'));
        }
        return $oRecipientCollection->count();
    }

    /**
     * checkEMail returns true, if there is no recipient with the same e-mail address; otherwise false
     *
     * @param $email string e-mail
     * @return NewsletterRecipient|false recipient item if item with e-mail exists, false otherwise
     * @throws cException
     */
    public function emailExists($email)
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        $oRecipientCollection = new NewsletterRecipientCollection();
        $oRecipientCollection->setWhere('idclient', $clientId);
        $oRecipientCollection->setWhere('idlang', $languageId);
        $oRecipientCollection->setWhere('email', cString::toLowerCase($email));
        $oRecipientCollection->query();

        if ($oItem = $oRecipientCollection->next()) {
            return $oItem;
        } else {
            return false;
        }
    }

    /**
     * Sets a key for all recipients without key or an old key (len(key) <> 30)
     *
     * @return int Number of updated keys
     * @throws cDbException|cException
     */
    public function updateKeys(): int
    {
        $this->setWhere("LENGTH(hash)", 30, "<>");
        $this->query();

        $iUpdated = $this->count();
        while ($oItem = $this->next()) {
            // Generating UID, 30 characters
            $oItem->set('hash', cString::getPartOfString(md5(rand()), 0, 17) . uniqid());
            $oItem->store();
        }

        return $iUpdated;
    }

}

/**
 * Single Recipient Item
 */
class NewsletterRecipient extends Item
{
    /**
     * Constructor Function
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('news_rcp'), 'idnewsrcp');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * @inheritDoc
     * @throws cException
     */
    public function store()
    {
        $auth = cRegistry::getAuth();

        $this->set('lastmodified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->getUserId());
        $success = parent::store();

        // @todo do update below only if code from above was successfully

        // Update name, email and newsletter type for recipients in pending
        // newsletter jobs
        $name = $this->get('name');
        $email = $this->get('email');
        if ($name == '') {
            $name = $email;
        }
        $newsType = $this->get('news_type');

        $oLogs = new NewsletterLogCollection();
        $oLogs->setWhere('idnewsrcp', $this->get($this->getPrimaryKeyName()));
        $oLogs->setWhere('status', 'pending');
        $oLogs->query();

        while ($oLog = $oLogs->next()) {
            $oLog->set('rcpname', $name);
            $oLog->set('rcpemail', $email);
            $oLog->set('rcpnewstype', $newsType);
            $oLog->store();
        }

        return $success;
    }

    /**
     * User-defined setter for newsletter recipients fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idnewsrcp':
            case 'idclient':
            case 'idlang':
            case 'confirmed':
            case 'deactivated':
            case 'news_type':
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
            case 'idnewsrcp':
            case 'idclient':
            case 'idlang':
            case 'confirmed':
            case 'deactivated':
            case 'news_type':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

}
