<?php

/**
 * This file contains the nav sub collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * File collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiNavSub>
 */
class cApiNavSubCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('nav_sub'), 'idnavs');
        $this->_setItemClass('cApiNavSub');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiNavMainCollection');
        $this->_setJoinPartner('cApiAreaCollection');
    }

    /**
     * Create new item with given values.
     *
     * @param int $navm
     * @param int|string $area AreaId or area name
     * @param int $level
     * @param string $location
     * @param int $online [optional]
     * @return cApiNavSub
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($navm, $area, $level, $location, $online = 1)
    {
        $item = $this->createNewItem();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy('name', $area);

            if ($c->isLoaded()) {
                $area = $c->get('idarea');
            } else {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
            }
        }

        $item->set('idnavm', $navm);
        $item->set('idarea', $area);
        $item->set('level', $level);
        $item->set('location', $location);
        $item->set('online', $online);

        $item->store();

        return $item;
    }

    /**
     * Returns sub navigation by area name
     *
     * @param string $area
     * @param int $level [optional]
     * @param int $online [optional]
     * @return array List of associative arrays like
     *      <pre>
     *      $arr[] = [
     *          'location' => location xml path
     *          'caption'  => The translation of location from XML file
     *          'name'     => area name for sub navigation item
     *          'menuless' => Menuless state
     *      ];
     *      </pre>
     * @throws cDbException|cException
     */
    public function getSubnavigationsByAreaName($area, $level = 1, $online = 1): array
    {
        $level = (int)$level;
        $online = (1 == $online) ? 1 : 0;

        $nav = new cGuiNavigation();

        $sql = "SELECT
                    ns.location AS location,
                    a.name AS name,
                    a.menuless AS menuless
                FROM
                    " . cDb::getTableName('area') . " AS a,
                    " . $this->table . " AS ns
                WHERE
                    a.idarea = ns.idarea
                AND
                    ns.level = " . $level . "
                AND
                    ns.online = " . $online . "
                AND (
                    a.parent_id = '" . $this->db->escape($area) . "'
                    OR
                    a.name = '" . $this->db->escape($area) . "'
                )
                ORDER BY
                    a.parent_id ASC,
                    ns.idnavs ASC";

        $this->db->query($sql);

        $areasNsRs = [];
        while ($this->db->nextRecord()) {
            $rs = $this->db->toArray();
            $rs['caption'] = $nav->getName($rs['location']);
            $areasNsRs[] = $rs;
        }

        return $areasNsRs;
    }

}

/**
 * NavMain item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiNavSub extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('nav_sub'), 'idnavs');
        $this->setFilters(['addslashes'], ['stripslashes']);
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for navsub fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idarea':
            case 'idnavm':
            case 'level':
                $value = cSecurity::toInteger($value);
                break;
            case 'online':
                $value = (1 == $value) ? 1 : 0;
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
