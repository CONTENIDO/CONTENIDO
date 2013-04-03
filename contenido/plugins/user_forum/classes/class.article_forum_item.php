<?php
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 *
 * @package plugins/user_forum
 * @version SVN Revision $Rev:$
 * @author claus.schunk
 * @copyright four for business AG
 * @link http://www.4fb.de
 */
class ArticleForumItem extends Item {

    protected $cfg;

    protected $db;

    public function __construct() {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
    }

    /**
     * returns current config
     */
    public function getCfg() {
        return $this->cfg;
    }

}
?>