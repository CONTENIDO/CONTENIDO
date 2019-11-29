<?php

/**
 *
 * @author marcus.gnass
 */
class DogCollection extends ItemCollection
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
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
        $this->_setItemClass('DogItem');
        if (false !== $where) {
            $this->select($where);
        }
    }
}

/**
 *
 * @author marcus.gnass
 */
class DogItem extends Item
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
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }
}
