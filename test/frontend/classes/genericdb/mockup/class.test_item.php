<?php

/**
 *
 * @author marcus.gnass
 */
class TestCollection extends ItemCollection {

    /**
     *
     * @param string|bool $where
     */
    public function __construct($where = false) {
        parent::__construct(cRegistry::getDbTableName('con_test'), 'ID');
        $this->_setItemClass('TestItem');
        if (false !== $where) {
            $this->select($where);
        }
    }

}

/**
 *
 * @author marcus.gnass
 */
class TestItem extends Item {

    /**
     *
     * @param string|bool $id
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('con_test'), 'ID');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Mapper function to expose ability to set loaded variable for unit tests
     *
     * @param bool $value
     *         Whether an item has been loaded
     */
    public function setLoaded($value) {
        static::_setLoaded($value);
    }
}
