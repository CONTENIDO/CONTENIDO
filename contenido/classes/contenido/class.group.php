<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Group class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.1
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Group collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiGroupCollection extends ItemCollection
{
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['groups'], 'group_id');
        $this->_setItemClass('cApiGroup');
    }

    /**
     * Returns the groups a user is in
     * @param   string    $userid
     * @return  cApiGroup[]  List of groups
     */
    public function fetchByUserID($userid)
    {
        global $cfg;

        $aIds = array();
        $aGroups = array();

        $sql = "SELECT a.group_id FROM `%s` AS a, `%s` AS b "
             . "WHERE (a.group_id  = b.group_id) AND (b.user_id = '%s')";

        $this->db->query($sql, $this->table, $cfg['tab']['groupmembers'], $userid);
        $this->_lastSQL = $sql;

        while ($this->db->next_record()) {
            $aIds[] = $this->db->f('group_id');
        }

        if (0 === count($aIds)) {
            return $aGroups;
        }

        $where = "group_id IN ('" . implode("', '", $aIds) .  "')";
        $this->select($where);
        while ($oItem = $this->next()) {
            $aGroups[] = clone $oItem;
        }

        return $aGroups;
    }

}


/**
 * Group item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiGroup extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['groups'], 'group_id');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}

?>