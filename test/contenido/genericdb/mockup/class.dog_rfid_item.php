<?php

/**
 * @author marcus.gnass
 * @method DogRfidItem createNewItem
 * @method DogRfidItem|bool next
 */
class DogRfidCollection extends ItemCollection
{
    /**
     *
     * @param string|bool $where
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct(cRegistry::getDbTableName('con_test_rfid_dog'), 'dog_id');
        $this->_setItemClass('DogRfidItem');
        if (false !== $where) {
            $this->select($where);
        }
    }
}

/**
 * @author marcus.gnass
 */
class DogRfidItem extends Item
{
    /**
     *
     * @param int|bool $id
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cRegistry::getDbTableName('con_test_rfid_dog'), 'dog_id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }
}
