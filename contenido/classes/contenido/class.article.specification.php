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
 * @extends ItemCollection<cApiArticleSpecification>
 */
class cApiArticleSpecificationCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdAndLanguageIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'client';

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'lang';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('art_spec'), 'idartspec');
        $this->_setItemClass('cApiArticleSpecification');
    }

    /**
     * Returns all article specifications by client and language.
     *
     * @throws cDbException|cException
     */
    public function fetchByClientLang(int $client, int $lang, string $orderBy = ''): array
    {
        $this->select("`client` = " . $client . " AND `lang` = " . $lang, '', $this->escape($orderBy));
        $entries = [];
        while ($entry = $this->next()) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

    /**
     * Sets the online status of an article specification.
     *
     * @param int $online The online status `0` or `1`, default is `0`.
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function setOnline(int $idArtSpec, int $online): bool
    {
        return cSecurity::toBoolean($this->db->query(
            'UPDATE `%s` SET `online` = %d WHERE `idartspec` = %d',
            $this->getTable(),
            $online === 1 ? 1 : 0,
            $idArtSpec
        ));
    }

    /**
     * Sets default article specification for a specific client and language.
     *
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function setDefaultArtSpec(int $idArtSpec, int $idClient, int $languageId): bool
    {
        // First reset the current default article specification for client and language.
        $sql = 'UPDATE `%s` SET `artspecdefault` = 0 WHERE `client` = %d AND `lang` = %d';
        if ($this->db->query($sql, $this->table, $idClient, $languageId)) {
            // Then set the new default article specification
            $sql = 'UPDATE `%s` SET `artspecdefault` = 1 WHERE `idartspec` = %d';
            return cSecurity::toBoolean($this->db->query($sql, $this->table, $idArtSpec));
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
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('art_spec'), 'idartspec');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}
