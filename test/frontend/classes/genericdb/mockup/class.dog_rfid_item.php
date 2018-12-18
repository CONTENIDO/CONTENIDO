<?php

/**
 *
 * @author marcus.gnass
 */
class DogRfidCollection extends ItemCollection {

    /**
     *
     * @param unknown_type $where
     */
    public function __construct($where = false) {
        parent::__construct(cRegistry::getDbTableName('con_test_rfid_dog'), 'dog_id');
        $this->_setItemClass('DogRfidItem');
        if (false !== $where) {
            $this->select($where);
        }
    }

}

/**
 *
 * @author marcus.gnass
 */
class DogRfidItem extends Item {

    /**
     *
     * @param unknown_type $id
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('con_test_rfid_dog'), 'dog_id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}
