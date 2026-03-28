<?php

/**
 * This file contains the meta type collection and item class.
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
 * Metatype collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiMetaType>
 */
class cApiMetaTypeCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('meta_type'), 'idmetatype');
        $this->_setItemClass('cApiMetaType');
    }

    /**
     * Creates a meta type entry.
     *
     * @param string $metaType
     * @param string $fieldType
     * @param int $maxLength
     * @param string $fieldName
     * @return cApiMetaType
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($metaType, $fieldType, $maxLength, $fieldName)
    {
        $oItem = $this->createNewItem();

        $oItem->set('metatype', $metaType);
        $oItem->set('fieldtype', $fieldType);
        $oItem->set('maxlength', $maxLength);
        $oItem->set('fieldname', $fieldName);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns all available meta-tag type entries.
     *
     * @return cApiMetaType[]
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function fetchAll(): array
    {
        $this->select();

        $entries = [];
        while ($entry = $this->next()) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

}

/**
 * Metatype item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiMetaType extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('meta_type'), 'idmetatype');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idmetatype':
            case 'maxlength':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * @inheritDoc
     * @since CONTENIDO 4.10.2
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'idmetatype':
            case 'maxlength':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

}
