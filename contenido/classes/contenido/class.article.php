<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Category access class
 *
 * @package CONTENIDO API
 * @version 1.3
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.str.php');

/**
 * Article collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiArticleCollection extends ItemCollection {

    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art'], 'idart');
        $this->_setItemClass('cApiArticle');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates an article item entry
     *
     * @param int $idclient
     * @return cApiArticle
     */
    public function create($idclient) {
        $item = parent::createNewItem();

        $item->set('idclient', (int) $idclient);
        $item->store();

        return $item;
    }

    /**
     * Returns list of ids by given client id.
     *
     * @param int $idclient
     * @return array
     */
    public function getIdsByClientId($idclient) {
        $sql = "SELECT idart FROM `%s` WHERE idclient=%d";
        $this->db->query($sql, $this->table, $idclient);
        $list = array();
        while ($this->db->next_record()) {
            $list[] = $this->db->f('idart');
        }
        return $list;
    }

}

/**
 * Article item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiArticle extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art'], 'idart');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Returns the link to the current object.
     * @param integer $changeLangId change language id for URL (optional)
     * @return string link
     */
    public function getLink($changeLangId = 0) {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = array();
        $options['idart'] = $this->get('idart');
        $options['lang'] = ($changeLangId == 0) ? cRegistry::getLanguageId() : $changeLangId;
        if ($changeLangId > 0) {
            $options['changelang'] = $changeLangId;
        }

        return cUri::getInstance()->build($options);
    }

}
