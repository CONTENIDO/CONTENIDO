<?php
defined('CON_FRAMEWORK') or die('Illegal call');
class ArticleForum extends Item {

    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['user_forum'], 'id_user_forum');
        $this->setFilters(array(), array());
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}

?>