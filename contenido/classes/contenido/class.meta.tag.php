<?php

/**
 * This file contains the meta tag collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Metatag collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiMetaTag createNewItem
 * @method cApiMetaTag|bool next
 */
class cApiMetaTagCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        $table = cRegistry::getDbTableName('meta_tag');
        parent::__construct($table, 'idmetatag');
        $this->_setItemClass('cApiMetaTag');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiArticleLanguageCollection');
        $this->_setJoinPartner('cApiMetaTypeCollection');
    }

    /**
     * Creates a meta tag entry.
     *
     * @param int $idArtLang
     * @param int $idMetaType
     * @param string $metaValue
     * @return cApiMetaTag
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idArtLang, $idMetaType, $metaValue)
    {
        $oItem = $this->createNewItem();

        $oItem->set('idartlang', $idArtLang, false);
        $oItem->set('idmetatype', $idMetaType, false);
        $oItem->set('metavalue', $metaValue, false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a meta tag entry by article language and meta type.
     *
     * @param int $idArtLang
     * @param int $idMetaType
     * @return ?cApiMetaTag
     * @throws cDbException|cException
     */
    public function fetchByArtLangAndMetaType($idArtLang, $idMetaType)
    {
        $where = sprintf('`idartlang` = %d AND `idmetatype` = %d', $idArtLang, $idMetaType);
        $this->select($where);
        return $this->next();
    }

    /**
     * Returns meta tag ids (idmetatag) by passed article language id.
     *
     * @param int $idArtLang Article language id
     * @return int[] List of meta tag ids
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getIdMetatagsByIdArtLang(int $idArtLang): array
    {
        if ($idArtLang <= 0) {
            return [];
        }
        $sql = 'SELECT `idmetatag` FROM `%s` WHERE `idartlang` = %d';
        $this->db->query($sql, $this->table, $idArtLang);
        $metaTagIds = [];
        while ($this->db->nextRecord()) {
            $metaTagIds[] = cSecurity::toInteger($this->db->f('idmetatag'));
        }
        return $metaTagIds;
    }

}

/**
 * Metatag item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTag extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        $table = cRegistry::getDbTableName('meta_tag');
        parent::__construct($table, 'idmetatag');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates meta value of an entry.
     *
     * @param string $metaValue
     * @throws cDbException|cInvalidArgumentException
     */
    public function updateMetaValue($metaValue): bool
    {
        $this->set('metavalue', $metaValue, false);
        return $this->store();
    }

    /**
     * Predefined setter for meta tag fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idmetatype':
            case 'idartlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Creates a new, editable Version with same properties
     *
     * @param string $version
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function markAsEditable($version)
    {
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        $metaTagVersionColl->create(
            $this->getField('idmetatag'),
            $this->getField('idartlang'),
            $this->getField('idmetatype'),
            $this->getField('metavalue'),
            $version
        );
    }

}
