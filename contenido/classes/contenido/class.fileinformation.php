<?php

/**
 * This file contains the file information collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Konstantinos Katikakis
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.file.php');

/**
 * File information collection.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiFileInformation>
 */
class cApiFileInformationCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('file_information'), 'idsfi');
        $this->_setItemClass('cApiFileInformation');
    }

    /**
     * Creates a new entry in the database
     *
     * @param string $typeContent Type of the entry
     * @param string $filename Name of the file
     * @param string $description [optional] An optional description
     * @return cApiFileInformation The new item
     * @throws cDbException|cException|cInvalidArgumentException
     * @todo  Pass additional fields as optional parameters
     */
    public function create($typeContent, $filename, $description = '')
    {
        $client = cRegistry::getClientId();
        $auth = cRegistry::getAuth();
        $item = new cApiFileInformation();
        $item->loadByMany(
            [
                'idclient' => $client,
                'type' => $typeContent,
                'filename' => $filename,
            ]
        );
        if (!$item->isLoaded()) {
            $item = $this->createNewItem();

            $item->set('idclient', $client);
            $item->set('type', $typeContent);
            $item->set('filename', $filename);
            $item->set('created', date('Y-m-d H:i:s'));
            $item->set('lastmodified', date('Y-m-d H:i:s'));
            $item->set('author', $auth->getUserId());
            $item->set('modifiedby', $auth->getUserId());
            $item->set('description', $description);
            $item->store();

            return $item;
        } else {
            return $this->updateFile($filename, $typeContent, $description);
        }
    }

    /**
     * updates a new entry in the database
     *
     * @param string $filename Name of the file
     * @param string $typeContent Type of the entry
     * @param string $description [optional] A optional description
     * @param string $newFilename [optional] A optional new filename
     * @param string $author [optional] A optional author
     * @return cApiFileInformation The updated item
     * @throws cDbException|cException|cInvalidArgumentException
     * @todo  Pass additional fields as optional parameters
     */
    public function updateFile($filename, $typeContent, $description = '', $newFilename = '', $author = '')
    {
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();
        $item = new cApiFileInformation();
        $item->loadByMany(
            [
                'idclient' => $client,
                'type' => $typeContent,
                'filename' => $filename,
            ]
        );
        $id = $item->get('idsfi');
        if ($item->isLoaded()) {
            $item->set('idsfi', $id);
            $item->set('lastmodified', date('Y-m-d H:i:s'));
            $item->set('description', $description);
            $item->set('modifiedby', $auth->getUserId());
            if (!empty($newFilename)) {
                $item->set('filename', $newFilename);
            }
            if (!empty($author)) {
                $item->set('author', $author);
            }
            $item->store();
        }

        return $item;
    }

    /**
     * Deletes all found items in the table matching the provided field, and its value.
     * Deletes also cached e entries and any existing properties.
     *
     * @param array $values With parameters
     * @throws cDbException|cException
     */
    public function removeFileInformation(array $values): bool
    {
        $item = new cApiFileInformation();
        $item->loadByMany($values);
        $idsfi = $item->get('idsfi');
        return $this->delete($idsfi);
    }

    /**
     * return an array with fileinformations from the database
     *
     * @param string $filename Name of the file
     * @param string $type Type of the entry
     * @return array{
     *     idsfi: int,
     *     created: string,
     *     lastmodified: string,
     *     author: string,
     *     modifiedby: string,
     *     description: string
     * } File information array or empty array on fail
     * @throws cDbException|cException
     */
    public function getFileInformation($filename, $type): array
    {
        $client = cRegistry::getClientId();
        $fileInformation = [];
        $item = new cApiFileInformation();
        $item->loadByMany(
            [
                'idclient' => $client,
                'type' => $type,
                'filename' => $filename,
            ]
        );
        if ($item->isLoaded()) {
            $fileInformation['idsfi'] = cSecurity::toInteger($item->get('idsfi'));
            $fileInformation['created'] = $item->get('created');
            $fileInformation['lastmodified'] = $item->get('lastmodified');
            $fileInformation['author'] = cSecurity::unFilter($item->get('author'));
            $fileInformation['modifiedby'] = $item->get('modifiedby');
            $fileInformation['description'] = cSecurity::unFilter($item->get('description'));
        }
        return $fileInformation;
    }
}

/**
 * File information item.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiFileInformation extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id [optional]
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('file_information'), 'idsfi');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
