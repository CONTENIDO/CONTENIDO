<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Frontend group classes
 *
 * Code is taken over from file contenido/classes/class.frontend.groups.php in favor of
 * normalizing API.
 *
 * Requirements: 
 * @con_php_req 5.0
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 * 
 * {@internal
 *   created  2011-09-20
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Frontend group collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiFrontendGroupCollection extends ItemCollection
{
    /**
     * Constructor Function
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroups'], 'idfrontendgroup');
        $this->_setItemClass('cApiFrontendGroup');
    }

    /**
     * Creates a new group
     * @param $groupname string Specifies the groupname
     * @param $password string Specifies the password (optional)
     */
    public function create($groupname)
    {
        global $client;

        $group = new cApiFrontendGroup();

        #$_arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

        $mangledGroupName = $group->_inFilter($groupname);
        $this->select("idclient = " . (int) $client . " AND groupname = '" . $mangledGroupName . "'");

        if ($obj = $this->next()) {
            $groupname = $groupname. md5(rand());
        }

        $item = parent::create();

        $item->set('idclient', $client);
        $item->set('groupname', $groupname);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from groupmember table
     * before deleting group
     *
     * @param $itemID int specifies the frontend user group
     */
    public function delete($itemID)
    {
        $associations = new cApiFrontendGroupMemberCollection();
        $associations->select('idfrontendgroup = ' . (int) $itemID);

        while ($item = $associations->next()) {
            $associations->delete($item->get('idfrontendgroupmember')); 
        }
        parent::delete($itemID); 
    }
}


/**
 * Frontend group item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiFrontendGroup extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroups'], 'idfrontendgroup');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}


################################################################################
# Old versions of frontend group item collection and frontend group item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in 
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Frontend group collection
 * @deprecated  [2011-09-20] Use cApiFrontendGroupCollection instead of this class.
 */
class FrontendGroupCollection extends cApiFrontendGroupCollection
{
    public function __construct()
    {
        cDeprecated("Use class cApiFrontendGroupCollection instead");
        parent::__construct();
    }
    public function FrontendGroupCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}


/**
 * Single frontend group item
 * @deprecated  [2011-09-20] Use cApiFrontendGroup instead of this class.
 */
class FrontendGroup extends cApiFrontendGroup
{
    public function __construct($mId = false)
    {
        cDeprecated("Use class cApiFrontendGroup instead");
        parent::__construct($mId);
    }
    public function FrontendGroup($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
}

?>