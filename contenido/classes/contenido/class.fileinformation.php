<?php

/**
 * This file contains the file information collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Konstantinos Katikakis
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.file.php');

/**
 * File information collection.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFileInformationCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['file_information'], 'idsfi');
        $this->_setItemClass('cApiFileInformation');
    }

    /**
     * Creates a new entry in the database
     *
     * @todo  Pass additional fields as optional parameters
     *
     * @param string $typeContent type of the entry
     * @param string $filename    name of the file
     * @param string $description an optional description
     *
     * @return cApiFileInformation
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($typeContent, $filename, $description = '')
    {
        $item = new cApiFileInformation();
        $item->loadByMany(['idclient' => cRegistry::getClientId(), 'type' => $typeContent, 'filename' => $filename]);

        if ($item->isLoaded()) {
            $item = $this->updateFile($filename, $typeContent, $description);
        } else {
            /** @var cApiFileInformation $item */
            $item = $this->createNewItem();
            $item->set('idclient', cRegistry::getClientId());
            $item->set('type', $typeContent);
            $item->set('filename', $filename);
            $item->set('created', date('Y-m-d H:i:s'));
            $item->set('lastmodified', date('Y-m-d H:i:s'));
            $item->set('author', cRegistry::getAuth()->auth['uid']);
            $item->set('modifiedby', cRegistry::getAuth()->auth['uid']);
            $item->set('description', $description);
            $item->store();
        }

        return $item;
    }

    /**
     * updates a new entry in the database
     *
     * @todo  Pass additional fields as optional parameters
     *
     * @param string $filename
     *                            name of the file
     * @param string $typeContent
     *                            type of the entry
     * @param string $description [optional]
     *                            an optional description
     * @param string $newFilename [optional]
     *                            an optional new filename
     * @param string $author      [optional]
     *                            an optional author
     *
     * @return cApiFileInformation
     *                            the updated item
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function updateFile($filename, $typeContent, $description = '', $newFilename = '', $author = '') {
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();
        $item = new cApiFileInformation();
        $item->loadByMany(array(
            'idclient' => $client,
            'type' => $typeContent,
            'filename' => $filename
        ));
        $id = $item->get('idsfi');
        if ($item->isLoaded()) {
            $item->set('idsfi', $id);
            $item->set('lastmodified', date('Y-m-d H:i:s'));
            $item->set('description', $description);
            $item->set('modifiedby', $auth->auth['uid']);
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
     * Deletes all found items in the table matching the passed field and it's
     * value.
     * Deletes also cached e entries and any existing properties.
     *
     * @param array $values
     *         with parameters
     * @return bool
     * 
     * @throws cDbException
     * @throws cException
     */
    public function removeFileInformation(array $values) {
        $item = new cApiFileInformation();
        $item->loadByMany($values);
        $idsfi = $item->get('idsfi');
        return $this->delete($idsfi);
    }

    /**
     * return an array with fileinformations from the database
     *
     * @param string $filename
     *         name of the file
     * @param string $type
     *         type of the entry
     * @return array
     * 
     * @throws cDbException
     * @throws cException
     */
    public function getFileInformation($filename, $type) {
        $client = cRegistry::getClientId();
        $fileInformation = array();
        $item = new cApiFileInformation();
        $item->loadByMany(array(
            'idclient' => $client,
            'type' => $type,
            'filename' => $filename
        ));
        if ($item->isLoaded()) {
            $fileInformation['idsfi'] = $item->get('idsfi');
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFileInformation extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param bool $id [optional]
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg['tab']['file_information'], 'idsfi');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
