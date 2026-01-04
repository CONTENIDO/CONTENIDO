<?php

/**
 * This file contains the meta tag version collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Jann Dieckmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Metatag version collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiMetaTagVersion>
 */
class cApiMetaTagVersionCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('meta_tag_version'), 'idmetatagversion');
        $this->_setItemClass('cApiMetaTagVersion');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiArticleLanguageVersionCollection');
        $this->_setJoinPartner('cApiMetaTypeCollection');
    }

    /**
     * Creates a meta tag entry.
     *
     * @param int $idMetaTag
     * @param int $idArtLang
     * @param int $idMetaType
     * @param string $metaValue
     * @param string $version
     * @return cApiMetaTagVersion
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idMetaTag, $idArtLang, $idMetaType, $metaValue, $version)
    {
        // create item
        $item = $this->createNewItem();

        $item->set('idmetatag', $idMetaTag, false);
        $item->set('idartlang', $idArtLang, false);
        $item->set('idmetatype', $idMetaType, false);
        $item->set('metavalue', $metaValue, false);
        $item->set('version', $version, false);
        $item->store();

        return $item;
    }

    /**
     * Returns a meta tag entry by article language and meta type and version.
     *
     * @param int $idArtLang
     * @param int $idMetaType
     * @param int $version
     * @return cApiMetaTagVersion
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function fetchByArtLangMetaTypeAndVersion($idArtLang, $idMetaType, $version): cApiMetaTagVersion
    {
        $sql = 'SELECT idmetatagversion FROM %s
                WHERE (idmetatype, version)
                    IN (SELECT idmetatype, max(version)
                    FROM %s
                    WHERE idartlang = %d AND version <= %d AND idmetatype = %d group by idmetatype)
                AND idartlang = %d';

        $this->db->query(
            $sql,
            cDb::getTableName('meta_tag_version'),
            cDb::getTableName('meta_tag_version'),
            (int)$idArtLang,
            (int)$version,
            (int)$idMetaType,
            (int)$idArtLang
        );

        $this->db->nextRecord();

        return new cApiMetaTagVersion($this->db->f('idmetatagversion'));
    }

    /**
     * Returns idmetatagversions by where-clause
     *
     * @return int[]
     * @throws cDbException|cException
     */
    public function fetchByArtLangAndMetaType(string $where): array
    {
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        $metaTagVersionColl->select($where);

        $ids = [];
        while ($item = $metaTagVersionColl->next()) {
            $ids[] = cSecurity::toInteger($item->get('idmetatagversion'));
        }
        return $ids;
    }

}

/**
 * Metatag version item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTagVersion extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('meta_tag_version'), 'idmetatagversion');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates meta value of an entry.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function updateMetaValue(string $metaValue): bool
    {
        $this->set('metavalue', $metaValue, false);
        return $this->store();
    }

    /**
     * Marks this meta value as current.
     *
     * @return bool|void
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function markAsCurrent()
    {
        $metaTagColl = new cApiMetaTagCollection();
        $metaTag = $metaTagColl->fetchByArtLangAndMetaType($this->get('idartlang'), $this->get('idmetatype'));
        if ($metaTag != NULL) {
            $metaTag->set('metavalue', $this->get('metavalue'), false);
            return $metaTag->store();
        } else {
            $metaTag = new cApiMetaTagCollection();
            $metaTag->create($this->get('idartlang'), $this->get('idmetatype'), $this->get('metavalue'));
        }
    }

    /**
     * Marks this meta value as editable.
     *
     * @param int $version
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function markAsEditable($version)
    {
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        $metaTagVersionColl->create($this->get('idmetatag'), $this->get('idartlang'), $this->get('idmetatype'), $this->get('metavalue'), $version);
    }

    /**
     * User-defined setter for meta tag fields.
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

}
