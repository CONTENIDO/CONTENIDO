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
class cApiMetaTagCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
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
     * @param int    $iIdArtLang
     * @param int    $iIdMetaType
     * @param string $sMetaValue
     *
     * @return cApiMetaTag
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($iIdArtLang, $iIdMetaType, $sMetaValue) {
        $oItem = $this->createNewItem();

        $oItem->set('idartlang', $iIdArtLang, false);
        $oItem->set('idmetatype', $iIdMetaType, false);
        $oItem->set('metavalue', $sMetaValue, false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a meta tag entry by article language and meta type.
     *
     * @param int $iIdArtLang
     * @param int $iIdMetaType
     * @return cApiMetaTag|NULL
     * @throws cDbException|cException
     */
    public function fetchByArtLangAndMetaType($iIdArtLang, $iIdMetaType) {
        $where = sprintf('`idartlang` = %d AND `idmetatype` = %d', $iIdArtLang, $iIdMetaType);
        $this->select($where);
        return $this->next();
    }

    /**
     * Returns meta tag ids (idmetatag) by passed article language id.
     *
     * @since CONTENIDO 4.10.2
     * @param int $idArtLang Article language id
     * @return int[] List of meta tag ids
     * @throws cDbException|cException
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
     * @param mixed $mId
     *         Specifies the ID of item to load
     *
     * @throws cDbException|cException
     */
    public function __construct($mId = false) {
        $table = cRegistry::getDbTableName('meta_tag');
        parent::__construct($table, 'idmetatag');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates meta value of an entry.
     *
     * @param string $sMetaValue
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function updateMetaValue($sMetaValue) {
        $this->set('metavalue', $sMetaValue, false);
        return $this->store();
    }

    /**
     * predefined setter for meta tag fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idmetatype':
            case 'idartlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

    /**
     * Creates a new, editable Version with same properties
     *
     * @param string $version
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function markAsEditable($version) {
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
