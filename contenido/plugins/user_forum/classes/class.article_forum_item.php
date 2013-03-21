<?php
defined('CON_FRAMEWORK') or die('Illegal call');
class ArticleForumItem extends Item {

    protected $cfg;

    protected $db;
   // protected $item;

    public function __construct() {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
        $this->_setItemClass('ArticleForumItem');
    }

}
