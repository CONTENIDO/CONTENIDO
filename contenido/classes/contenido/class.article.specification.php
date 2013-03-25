<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Article specification class
 *
 * Code is taken over from file contenido/classes/class.artspec.php in favor of
 * normalizing API.
 *
 * @package CONTENIDO API
 * @version 0.1
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Article specification collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiArticleSpecificationCollection extends ItemCollection {

    /**
     * Constructor function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['art_spec'], 'idartspec');
        $this->_setItemClass('cApiArticleSpecification');
    }

    /**
     * Returns all article specifications by client and language.
     *
     * @param int $client
     * @param int $lang
     * @param string $orderby Order statement, like "artspec ASC"
     * @return cApiGroupProperty[]
     */
    public function fetchByClientLang($client, $lang, $orderBy = '') {
        $this->select("client=" . (int) $client . " AND lang=" . (int) $lang, '', $this->escape($orderBy));
        $entries = array();
        while (($entry = $this->next()) !== false) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

}

/**
 * Article specification item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiArticleSpecification extends Item {

    /**
     * Constructor function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art_spec'], 'idartspec');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
