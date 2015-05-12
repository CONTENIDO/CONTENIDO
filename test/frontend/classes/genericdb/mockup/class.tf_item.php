<?php

/**
 *
 * @author marcus.gnass
 */
class TFCollection extends ItemCollection {

    /**
     *
     * @param string|bool $where
     */
    public function __construct($where = false) {
        parent::__construct(cRegistry::getDbTableName('con_test'), 'ID');
        // $this->_setItemClass('TestItem');
        if (false !== $where) {
            $this->select($where);
        }
    }

}

/**
 *
 * @author marcus.gnass
 */
class TFItem extends Item {

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

}
