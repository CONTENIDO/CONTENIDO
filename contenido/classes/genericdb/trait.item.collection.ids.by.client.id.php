<?php

/**
 * This file contains the trait for retrieving ids by client id.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Database
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Ids by language id trait for usage in classes extending {@see ItemCollection}.
 *
 * This trait is meant only for usage in ItemCollection classes, where the associated
 * table contains a client id foreign key field.
 * Therefore, it requires following properties to be set and methods to be implemented
 * by a ItemCollection class using this trait:
 * @property string fkClientIdName
 * @property cDb db
 * @method ItemCollection|string getTable
 * @method ItemCollection|string getPrimaryKeyName
 *
 * @package    Core
 * @subpackage Database
 */
trait cItemCollectionIdsByClientIdTrait
{

    /**
     * Returns ids of related table by the value of client id foreign key field.
     *
     * @param int $clientId
     * @return array List of ids
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function getIdsByClientId(int $clientId): array
    {
        $pkName = $this->getPrimaryKeyName();
        $list = [];

        $sql = "SELECT `:pk_field` FROM `:table` WHERE `:client_id_field` = :client_id_value";
        $this->db->query($sql, [
            'pk_field' => $pkName,
            'table' => $this->getTable(),
            'client_id_field' => $this->fkClientIdName,
            'client_id_value' => $clientId
        ]);

        while ($this->db->nextRecord()) {
            $list[] = cSecurity::toInteger($this->db->f($pkName));
        }

        return $list;
    }

}