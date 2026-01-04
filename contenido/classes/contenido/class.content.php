<?php

/**
 * This file contains the content collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiContent>
 */
class cApiContentCollection extends ItemCollection
{

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('content'), 'idcontent');
        $this->_setItemClass('cApiContent');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiArticleLanguageCollection');
        $this->_setJoinPartner('cApiTypeCollection');
    }

    /**
     * Creates a content entry.
     *
     * @param int $idArtLang
     * @param int $idType
     * @param int $typeId
     * @param string|mixed $value
     * @param int|mixed $version
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiContent
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $idArtLang, $idType, $typeId, $value, $version, $author = '', $created = '', $lastmodified = ''
    )
    {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $oItem = $this->createNewItem();

        $oItem->set('idartlang', $idArtLang);
        $oItem->set('idtype', $idType);
        $oItem->set('typeid', $typeId);
        $oItem->set('value', $value);
        $oItem->set('version', $version);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastmodified);

        $oItem->store();

        return $oItem;
    }
}

/**
 * Content item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiContent extends Item
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('content'), 'idcontent');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for item fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idartlang':
            case 'idtype':
            case 'typeid':
            case 'version':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Creates a new, editable Version with same properties as this Content
     *
     * @param string $version
     * @param mixed $deleted
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function markAsEditable($version, $deleted)
    {
        $parameters = $this->values;
        $parameters['version'] = $version;
        $contentVersionColl = new cApiContentVersionCollection();
        $contentVersion = $contentVersionColl->create($parameters);
        if ($deleted == 1) {
            $contentVersion->set('deleted', $deleted);
        }
        $contentVersion->store();
    }

    /**
     * Loads a content entry by its article language id, idtype and type id.
     *
     * @param int $idartlang Article language id
     * @param int $idtype Content type id (e.g. id of `CONTENT_TYPE`)
     * @param int $typeid Content id (e.g. the ID in `CONTENT_TYPE[ID]`)
     * @throws cException
     */
    public function loadByArticleLanguageIdTypeAndTypeId($idartlang, $idtype, $typeid): bool
    {
        $aProps = [
            'idartlang' => $idartlang,
            'idtype' => $idtype,
            'typeid' => $typeid,
        ];
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        } else {
            $where = "`idartlang` = %d AND `idtype` = %d AND `typeid` = %d";
            $where = $this->db->prepare($where, $idartlang, $idtype, $typeid);
            return $this->_loadByWhereClause($where);
        }
    }

}
