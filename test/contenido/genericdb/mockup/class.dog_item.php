<?php

/**
 * @author marcus.gnass
 * @method DogItem createNewItem
 * @method DogItem|bool next
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
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
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
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }
}
