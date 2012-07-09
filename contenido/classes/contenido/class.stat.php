<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Statistics management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2011-07-20
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Statistic collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiStatCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['stat'], 'idstat');
        $this->_setItemClass('cApiStat');
    }

    /**
     * Tracks a visit. Increments a existing entry or creates a new one.
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @param int $iIdClient
     */
    public function trackVisit($iIdCatArt, $iIdLang, $iIdClient) {
        $oStat = $this->fetchByCatArtAndLang($iIdCatArt, $iIdLang);
        if (is_object($oStat)) {
            $oStat->increment();
        } else {
            $this->create($iIdCatArt, $iIdLang, $iIdClient);
        }
    }

    /**
     * Creates a stat entry.
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @param int $iIdClient
     * @param int $iVisited
     * @return cApiStat
     */
    public function create($iIdCatArt, $iIdLang, $iIdClient, $iVisited = 1) {
        $oItem = parent::createNewItem();

        $oItem->set('visited', (int) $iVisited);
        $oItem->set('idcatart', (int) $iIdCatArt);
        $oItem->set('idlang', (int) $iIdLang);
        $oItem->set('idclient', (int) $iIdClient);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a stat entry by category article and language.
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @return cApiStat|null
     */
    public function fetchByCatArtAndLang($iIdCatArt, $iIdLang) {
        $this->select('idcatart=' . (int) $iIdCatArt . ' AND idlang=' . (int) $iIdLang);
        return $this->next();
    }

    /**
     * Deletes statistics entries by category id and article id.
     * @param   int  $idcat
     * @param   int  $idart
     * @return  int  Number of deleted items
     */
    public function deleteByCatAndArt($idcat, $idart) {
        $where = 'idcat = ' . (int) $idcat . ' AND idart = ' . (int) $idart;
        return $this->deleteByWhereClause($where);
    }

}

/**
 * Statistic item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiStat extends Item {

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['stat'], 'idstat');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    public function increment() {
        $this->set('visited', $this->get('visited') + 1);
        $this->store();
    }

}

?>