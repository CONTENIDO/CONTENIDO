<?php

/**
 * @author marcus.gnass
 * @extends ItemCollection<TFItem>
 */
class TFCollection extends ItemCollection
{
    /**
     *
     * @param string|bool $where
     *
     * @throws cDbException
     */
    public function __construct($where = false)
    {
        parent::__construct(cDb::getTableName('con_test'), 'ID');
        // $this->_setItemClass('TFItem');
        if (false !== $where) {
            $this->select($where);
        }
    }
}

/**
 * @author marcus.gnass
 */
class TFItem extends Item
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
}
