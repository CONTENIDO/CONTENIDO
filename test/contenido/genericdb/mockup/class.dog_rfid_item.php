<?php

/**
 * @author marcus.gnass
 * @extends ItemCollection<DogRfidItem>
 */
class DogRfidCollection extends ItemCollection
{
    /**
     *
     * @param string|bool $where
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct(cDb::getTableName('con_test_rfid_dog'), 'dog_id');
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
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('con_test_rfid_dog'), 'dog_id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }
}
