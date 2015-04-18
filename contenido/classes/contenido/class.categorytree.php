<?php
/**
 * This file contains the category tree collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Category tree collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryTreeCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select
     *         where clause to use for selection (see ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_tree'], 'idtree');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiCategoryCollection');

        $this->_setItemClass('cApiCategoryTree');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Returns category tree structure by selecting the data from several tables
     * ().
     *
     * @param int $client
     *         Client id
     * @param int $lang
     *         Language id
     * @return array
     *         Category tree structure as follows:
     *         <pre>
     *         $arr[n] (int) idtree value
     *         $arr[n]['idcat'] (int)
     *         $arr[n]['level'] (int)
     *         $arr[n]['idtplcfg'] (int)
     *         $arr[n]['visible'] (int)
     *         $arr[n]['name'] (string)
     *         $arr[n]['public'] (int)
     *         $arr[n]['urlname'] (string)
     *         $arr[n]['is_start'] (int)
     *         </pre>
     */
    function getCategoryTreeStructureByClientIdAndLanguageId($client, $lang) {
        global $cfg;

        $aCatTree = array();

        $sql = 'SELECT * FROM `:cat_tree` AS A, `:cat` AS B, `:cat_lang` AS C ' . 'WHERE A.idcat = B.idcat AND B.idcat = C.idcat AND C.idlang = :idlang AND idclient = :idclient ' . 'ORDER BY idtree';

        $sql = $this->db->prepare($sql, array(
            'cat_tree' => $this->table,
            'cat' => $cfg['tab']['cat'],
            'cat_lang' => $cfg['tab']['cat_lang'],
            'idlang' => (int) $lang,
            'idclient' => (int) $client
        ));
        $this->db->query($sql);

        while ($this->db->nextRecord()) {
            $aCatTree[$this->db->f('idtree')] = array(
                'idcat' => $this->db->f('idcat'),
                'level' => $this->db->f('level'),
                'idtplcfg' => $this->db->f('idtplcfg'),
                'visible' => $this->db->f('visible'),
                'name' => $this->db->f('name'),
                'public' => $this->db->f('public'),
                'urlname' => $this->db->f('urlname'),
                'is_start' => $this->db->f('is_start')
            );
        }

        return $aCatTree;
    }
}

/**
 * Category tree item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryTree extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_tree'], 'idtree');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
