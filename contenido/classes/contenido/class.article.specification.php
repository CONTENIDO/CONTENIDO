<?php

/**
 * This file contains the article specifications collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Article specification collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiArticleSpecification createNewItem
 * @method cApiArticleSpecification|bool next
 */
class cApiArticleSpecificationCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cRegistry::getDbTableName('art_spec'), 'idartspec');
        $this->_setItemClass('cApiArticleSpecification');
    }

    /**
     * Returns all article specifications by client and language.
     *
     * @param int $client
     * @param int $lang
     * @param string $orderBy
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function fetchByClientLang(int $client, int $lang, string $orderBy = ''): array
    {
        $this->select("`client` = " . $client . " AND `lang` = " . $lang, '', $this->escape($orderBy));
        $entries = [];
        while (($entry = $this->next()) !== false) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

    /**
     * Sets the online status of an article specification.
     *
     * @param int $idArtSpec
     * @param int $online The online status `0` or `1`, default is `0`.
     * @return bool
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function setOnline(int $idArtSpec, int $online): bool
    {
        $online = $online === 1 ? 1 : 0;
        $sql = 'UPDATE `%s` SET `online` = %d WHERE `idartspec` = %d';
        return (bool) $this->db->query($sql, $this->getTable(), $online, $idArtSpec);
    }

    /**
     * Sets default article specification for a specific client and language.
     *
     * @param int $idArtSpec
     * @param int $idClient
     * @param int $idLang
     * @return bool
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function setDefaultArtSpec(int $idArtSpec, int $idClient, int $idLang): bool
    {
        // First reset the current default article specification for client and language.
        $sql = 'UPDATE `%s` SET `artspecdefault` = 0 WHERE `client` = %d AND `lang` = %d';
        if ($this->db->query($sql, $this->table, $idClient, $idLang)) {
            // Then set the new default article specification
            $sql = 'UPDATE `%s` SET `artspecdefault` = 1 WHERE `idartspec` = %d';
            return (bool) $this->db->query($sql, $this->table, $idArtSpec);
        }

        return false;
    }
}

/**
 * Article specification item
 *
 * @package    Core
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
    public function __construct($mId = false)
    {
        parent::__construct(cRegistry::getDbTableName('art_spec'), 'idartspec');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
