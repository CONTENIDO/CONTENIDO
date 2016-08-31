<?php

/**
 * This file contains the meta tag version collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Jann Dieckmann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Metatag version collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTagVersionCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['meta_tag_version'], 'idmetatagversion');
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
     */
    public function create($idMetaTag, $idArtLang, $idMetaType, $metaValue, $version) {

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
     * @return cApiMetaTagVersion|NULL
     */
    public function fetchByArtLangMetaTypeAndVersion($idArtLang, $idMetaType, $version) {
        $sql = 'SELECT idmetatagversion FROM %s
                WHERE (idmetatype, version)
                    IN (SELECT idmetatype, max(version)
                    FROM %s
                    WHERE idartlang = %d AND version <= %d AND idmetatype = %d group by idmetatype)
                AND idartlang = %d';

        $this->db->query(
            $sql,
            cRegistry::getDbTableName('meta_tag_version'),
            cRegistry::getDbTableName('meta_tag_version'),
            (int) $idArtLang,
            (int) $version,
            (int) $idMetaType,
            (int) $idArtLang
        );

        $this->db->nextRecord();

        return new cApiMetaTagVersion($this->db->f('idmetatagversion'));
    }


    /**
     * Returns idmetatagversions by where-clause
     *
     * @param string $where
     * @return int[]
     */
    public function fetchByArtLangAndMetaType($where) {
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        $metaTagVersionColl->select($where);

        while($item = $metaTagVersionColl->next()){
            $ids[] = $item->get('idmetatagversion');
        }
        return $ids;

    }

}

/**
 * Metatag version item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTagVersion extends Item {

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id
     *         Specifies the ID of item to load
     */
    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg['tab']['meta_tag_version'], 'idmetatagversion');
        $this->setFilters(array(), array());
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates meta value of an entry.
     *
     * @param string $metaValue
     * @return bool
     */
    public function updateMetaValue($metaValue) {
        $this->set('metavalue', $metaValue, false);
        return $this->store();
    }

    /**
     * Marks this meta value as current.
     *
     * @return bool|void
     */
    public function markAsCurrent() {
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
     */
    public function markAsEditable($version) {
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        $metaTagVersionColl->create($this->get('idmetatag'), $this->get('idartlang'), $this->get('idmetatype'), $this->get('metavalue'), $version);
    }

    /**
     * Userdefined setter for meta tag fields.
     *
     * @see Item::setField()
     * @param string $name
     *         Field name
     * @param mixed $value
     *         Value to set
     * @param bool $safe
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $safe = true) {
        switch ($name) {
            case 'idartlang':
                $value = (int) $value;
                break;
            case 'idmetatype':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
