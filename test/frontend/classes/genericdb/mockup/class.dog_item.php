<?php

class DogCollection extends ItemCollection {

    public function __construct($where = false) {
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
        $this->_setItemClass('DogItem');
        if (false !== $where) {
            $this->select($where);
        }
    }

}
class DogItem extends Item {

    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('con_test_dog'), 'id');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}
