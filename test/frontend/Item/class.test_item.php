<?php

class TestCollection extends ItemCollection {

    public function __construct($where = false) {
        parent::__construct(cRegistry::getDbTableName('con_test'), 'ID');
        $this->_setItemClass('TestItem');
        if (false !== $where) {
            $this->select($where);
        }
    }

}
class TestItem extends Item {

    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('con_test'), 'ID');
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}
