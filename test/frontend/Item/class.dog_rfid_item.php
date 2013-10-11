<?php

class DogRfidCollection extends ItemCollection {

    public function __construct($where = false) {
        parent::__construct(cRegistry::getDbTableName('con_test_rfid_dog'), 'dog_id');
        $this->_setItemClass('DogRfidItem');
        if (false !== $where) {
            $this->select($where);
        }
    }

}
class DogRfidItem extends Item {

    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('con_test_rfid_dog'), 'dog_id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}
