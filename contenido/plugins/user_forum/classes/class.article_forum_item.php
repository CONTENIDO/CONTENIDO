<?php

/**
 * This file contains the item class for userforum plugin.
 *
 * @package    Plugin
 * @subpackage UserForum
 * @author     Claus Schunk
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions db-query for cfg.
 *
 * @package    Plugin
 * @subpackage UserForum
 */
class ArticleForumItem extends Item
{

    protected $cfg;

    protected $db;

    public function __construct()
    {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
    }

    /**
     * returns current config
     */
    public function getCfg()
    {
        return $this->cfg;
    }

}