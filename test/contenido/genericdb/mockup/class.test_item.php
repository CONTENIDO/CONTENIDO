<?php

/**
 * @author marcus.gnass
 * @extends ItemCollection<TestItem>
 */
class TestCollection extends ItemCollection
{
    /**
     *
     * @param string|bool $where
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct(cDb::getTableName('con_test'), 'ID');
        $this->_setItemClass('TestItem');
        if (false !== $where) {
            $this->select($where);
        }
    }
}

/**
 * @author marcus.gnass
 */
class TestItem extends Item
{
    /**
     *
     * @param string|bool $id
     *
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('con_test'), 'ID');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Mapper function to expose ability to set loaded variable for unit tests
     *
     * @param bool $loaded Whether an item has been loaded
     */
    public function setLoaded(bool $loaded)
    {
        static::_setLoaded($loaded);
    }
}
