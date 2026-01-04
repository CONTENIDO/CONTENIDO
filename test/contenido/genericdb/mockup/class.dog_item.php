<?php

/**
 * @author marcus.gnass
 * @extends ItemCollection<DogItem>
 */
class DogCollection extends ItemCollection
{
    /**
     *
     * @param string|bool $where
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct(cDb::getTableName('con_test_dog'), 'id');
        $this->_setItemClass('DogItem');
        if (false !== $where) {
            $this->select($where);
        }
    }
}

/**
 * @author marcus.gnass
 */
class DogItem extends Item
{
    /**
     *
     * @param int|bool $id
     *
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('con_test_dog'), 'id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }
}
