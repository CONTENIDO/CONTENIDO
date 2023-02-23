<?php
/**
 * This file contains the article specifications collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Article specification collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiArticleSpecification createNewItem
 * @method cApiArticleSpecification|bool next
 */
class cApiArticleSpecificationCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('art_spec'), 'idartspec');
        $this->_setItemClass('cApiArticleSpecification');
    }

    /**
     * Returns all article specifications by client and language.
     *
     * @param int    $client
     * @param int    $lang
     * @param string $orderBy
     *
     * @return array
     *
     * @throws cDbException
     * @throws cException
     */
    public function fetchByClientLang($client, $lang, $orderBy = '') {
        $this->select("client=" . (int) $client . " AND lang=" . (int) $lang, '', $this->escape($orderBy));
        $entries = [];
        while (($entry = $this->next()) !== false) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

}

/**
 * Article specification item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiArticleSpecification extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('art_spec'), 'idartspec');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
