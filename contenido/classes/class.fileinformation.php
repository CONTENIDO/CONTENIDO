<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * MySQL Driver for GenericDB
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.12
 * @author Konstantinos Katikakis
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
cInclude("includes", "functions.file.php");

/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * MySQL Driver for GenericDB
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.12
 * @author Konstantinos Katikakis
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cApiFileInformationCollection extends ItemCollection {

    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["file_information"], 'idsfi');
        $this->_setItemClass('cApiFileInformation');
    }

    /**
     * Creates a new entry in the database
     * @param  $typeContent  type of the entry
     * @param  $filename  name of the file
     * @param  $description  an optional description
     */

    public function create($typeContent, $filename, $description = '') {
        $client = cRegistry::getClientId();
        $auth = cRegistry::getAuth();
        $item = new cApiFileInformation();
        $item->loadByMany(array(
            'idclient' => $client,
            'type' => $typeContent,
            'filename' => $filename
        ));
        if (!$item->isLoaded()) {
            $item = parent::createNewItem();

            $item->set('idclient', $client);
            $item->set('type', $typeContent);
            $item->set('filename', $filename);
            $item->set('created', date('Y-m-d H:i:s'));
            $item->set('lastmodified', date('Y-m-d H:i:s'));
            $item->set('author', $auth->auth['uid']);
            $item->set('modifiedby', $auth->auth['uid']);
            $item->set('description', $description);
            $item->store();

            return $item->get('idsfi');
        } else {
            $this->updateFile($filename, $typeContent, $description);
        }
    }
    /**
     * updates a new entry in the database
     * @param  $typeContent  type of the entry
     * @param  $filename  name of the file
     * @param  $description  an optional description
     * @param  $newFilename  an optional new filename
     * @param  $author  an optional author
     *
     */
    public function updateFile($sFilename, $sTypeContent, $description = '', $newFilename = '', $author = '') {
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();
        $item = new cApiFileInformation();
        $item->loadByMany(array(
            'idclient' => $client,
            'type' => $sTypeContent,
            'filename' => $sFilename
        ));
        $id = $item->get('idsfi');
        if ($item->isLoaded()) {
            $item->set('idsfi', $id);
            $item->set('lastmodified', date("Y-m-d H:i:s"));
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
     * removes a new entry in the database
     * @param  array wioth parameters
     *
     */
    public function removeFileInformation($values) {
        $this->deleteByMany($values);
    }

    /**
     * return an array with fileinformations from the database
     * @param  $sType  type of the entry
     * @param  $filename  name of the file
     *
     * @return array
     */
    public function getFileInformation($sFilename, $sType) {
        $client = cRegistry::getClientId();
        $aFileInformation = array();
        $item = new cApiFileInformation();
        $item->loadByMany(array(
            'idclient' => $client,
            'type' => $sType,
            'filename' => $sFilename
        ));
        if ($item->isLoaded()) {
            $aFileInformation['idsfi'] = $item->get('idsfi');
            $aFileInformation['created'] = $item->get('created');
            $aFileInformation['lastmodified'] = $item->get('lastmodified');
            $aFileInformation['author'] = cSecurity::unFilter($item->get('author'));
            $aFileInformation['modifiedby'] = $item->get('modifiedby');
            $aFileInformation['description'] = cSecurity::unFilter($item->get('description'));
        }
        return $aFileInformation;
    }

}
class cApiFileInformation extends Item {

    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg["tab"]["file_information"], 'idsfi');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}

?>