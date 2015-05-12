<?php

/**
 *
 * @author marcus.gnass
 */
class DogCollection extends ItemCollection {

    /**
     *
     * @param unknown_type $where
     */
    public function __construct($where = false) {
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
class DogItem extends Item {

    /**
     *
     * @param unknown_type $id
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}
