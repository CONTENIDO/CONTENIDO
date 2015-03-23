<?php
/**
 * This file contains the module search class.
 * TODO: Rework comments of this class.
 *
 * @package    Core
 * @subpackage Backend
 * @version    SVN Revision $Rev:$
 *
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class performs the module search.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleSearch extends cModuleHandler {

    /**
     * Items/Element per page
     *
     * @var int
     */
    protected $_elementPerPage = '';

    /**
     * Order by
     *
     * @var string
     */
    protected $_orderBy = '';

    /**
     * Sort order
     *
     * @var string
     */
    protected $_sortOrder = '';

    /**
     * Module type
     *
     * @var string
     */
    protected $_moduleType = '';

    /**
     * Filter string
     *
     * @var string
     */
    protected $_filter = '';

    /**
     * Where should be searched (all, description, type, input, output)
     *
     * @var string
     */
    protected $_searchIn = '';

    /**
     * Selected page
     *
     * @var int
     */
    protected $_selectedPage = 1;

    /**
     * Result saved in a array
     *
     * @var array
     */
    protected $_result = array();

    /**
     * Print a array
     *
     * @param array $arg
     */
    private function _echo($arg) {
        echo '<pre>' . print_r($arg) . '</pre>';
    }

    public function __construct($searchOptions) {
        parent::__construct();
        
        $this->_elementPerPage = $searchOptions['elementPerPage'];
        $this->_orderBy = $searchOptions['orderBy'];
        $this->_sortOrder = $searchOptions['sortOrder'];
        $this->_moduleType = $searchOptions['moduleType'];
        $this->_filter = $searchOptions['filter'];
        $this->_searchIn = $searchOptions['searchIn'];
        $this->_selectedPage = $searchOptions['selectedPage'];
    }

    /**
     * Count result
     *
     * @return int count in result
     */
    public function getModulCount() {
        return count($this->_result);
    }

    /**
     * Search for modules in db columns and in filesystem (input and output
     * files)
     *
     * @return array result
     */
    public function searchForAllModules() {
        global $cfg, $client;

        $idClient = $client;
        // first fetch all modules for client
        // then apply _filter on input and output from files
        // then use the whitelisted id's and search for additional filter matches on database
        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s", $cfg['tab']['mod'], $idClient);
        
        
        $db = cRegistry::getDb();
        $db->query($sql);
        $moduleIds = array();
        
        // filter modules based on input and output
        while (($modul = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            if (strlen(stripslashes($this->_filter)) === 0
                    || strpos($this->readInput(), stripslashes($this->_filter)) !== false
                    || strpos($this->readOutput(), stripslashes($this->_filter)) !== false) {
                    $moduleIds[] = $db->f('idmod');
            }
        }
        
        // build query using whitelisted id's
        $idFilter = "";
        foreach ($moduleIds as $moduleId) {
            $idFilter .= " OR idmod=" . (int) $moduleId;
        }
        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
                            type LIKE '%s'
                            AND type LIKE '%s'
                            OR description LIKE '%s'
                            OR name LIKE  '%s'" . $idFilter . ")
                            ORDER BY %s %s", $cfg['tab']['mod'], $idClient, $this->_moduleType, '%' . $this->_filter . '%', '%' . $this->_filter . '%', '%' . $this->_filter . '%', $this->_orderBy, $this->_sortOrder);

        $db = cRegistry::getDb();
        $db->query($sql);
        $result = array();

        while (($modul = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = array(
                    'name' => $db->f('name'),
                    'description' => $db->f('description'),
                    'error' => $db->f('error'),
                    'input' => $this->readInput(),
                    'output' => $this->readOutput()
            );
        }

        return $result;
    }

    /**
     * Main method for the class. Search for modules in db and in input and
     * outputs files.
     *
     * @return array result
     */
    public function getModules() {
        $modules = array();

        switch ($this->_searchIn) {
            case 'all':
                $modules = $this->searchForAllModules();
                break;
            case 'name':
                $modules = $this->findeModulWithName();
                break;
            case 'description':
                $modules = $this->findModuleWithDescription();
                break;
            case 'type':
                $modules = $this->findModuleWithType();
                break;
            case 'input': // Search for modulname_input.php
                $modules = $this->findModulWithInput();
                break;
            case 'output': // Search fro modulname_output.php
                $modules = $this->findModulWithOutput();
                break;
        }

        $this->_result = $modules;
        if ($this->_elementPerPage > 0) {
            if (count($this->_result) < (($this->_page - 1) * $this->_elementPerPage)) {
                $this->_page = 1;
            }

            if ($this->_elementPerPage * ($this->_page) >= count($this->_result) + $this->_elementPerPage && $this->_page != 1) {
                $this->_page--;
            }
            return array_slice($modules, $this->_elementPerPage * ($this->_selectedPage - 1), $this->_elementPerPage, true);
        } else {
            return $modules;
        }
    }

    /**
     * Search for modules in "name" column of modul
     *
     * @return array result
     */
    public function findeModulWithName() {
        global $cfg, $client;
        $idClient = $client;

        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (type LIKE '%s' AND name LIKE '%s')
                        ORDER BY %s %s ", $cfg['tab']['mod'], $idClient, $this->_moduleType, '%' . $this->_filter . '%', $this->_orderBy, $this->_sortOrder);

        $db = cRegistry::getDb();
        $db->query($sql);
        $result = array();

        while (($module = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = array(
                    'name' => $db->f('name'),
                    'description' => $db->f('description'),
                    'error' => $db->f('error'),
                    'input' => $this->readInput(),
                    'output' => $this->readOutput()
            );
        }
        return $result;
    }

    /**
     * Search for modules in input file of the module
     *
     * @return array result
     */
    public function findModulWithInput() {
        global $cfg, $client;

        $idClient = $client;
        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND type LIKE '%s'
                        ORDER BY %s %s ", $cfg['tab']['mod'], $idClient, $this->_moduleType, $this->_orderBy, $this->_sortOrder);

        $db = cRegistry::getDb();
        $db->query($sql);
        $result = array();
        while (($module = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            if (strlen(stripslashes($this->_filter)) === 0
                || strpos($this->readInput(), stripslashes($this->_filter)) !== false) {
                $result[$db->f('idmod')] = array(
                        'name' => $db->f('name'),
                        'description' => $db->f('description'),
                        'error' => $db->f('error'),
                        'input' => $this->readInput(),
                        'output' => $this->readOutput()
                );
            }
        }

        return $result;
    }

    /**
     * Search for modules in output of modules of current client
     *
     * @return array result
     */
    public function findModulWithOutput() {
         global $cfg, $client;
        
        $result = array();

        $idClient = $client;
        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND type LIKE '%s'
                        ORDER BY %s %s ", $cfg['tab']['mod'], $idClient, $this->_moduleType, $this->_orderBy, $this->_sortOrder);

        $db = cRegistry::getDb();
        $db->query($sql);
        $result = array();
        while (($module = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            if (strlen(stripslashes($this->_filter)) === 0
                || strpos($this->readOutput(), stripslashes($this->_filter)) !== false) {
                $result[$db->f('idmod')] = array(
                        'name' => $db->f('name'),
                        'description' => $db->f('description'),
                        'error' => $db->f('error'),
                        'input' => $this->readInput(),
                        'output' => $this->readOutput()
                );
            }
        }

        return $result;
    }

    /**
     * Search for modules in type column
     *
     * @return array result
     */
    public function findModuleWithType() {
        global $cfg, $client;
        $idClient = $client;

        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
                            type LIKE '%s'
                            AND type LIKE '%s')
                            ORDER BY %s %s ", $cfg['tab']['mod'], $idClient, $this->_moduleType, '%' . $this->_filter . '%', $this->_orderBy, $this->_sortOrder);

        $db = cRegistry::getDb();
        $db->query($sql);
        $result = array();

        while (($module = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = array(
                    'name' => $db->f('name'),
                    'description' => $db->f('description'),
                    'error' => $db->f('error'),
                    'input' => $this->readInput(),
                    'output' => $this->readOutput()
            );
        }
        return $result;
    }

    /**
     * Search for modules in description column of modules
     *
     * @return array result
     */
    public function findModuleWithDescription() {
        global $cfg, $client;
        $idClient = $client;

        $sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
                            type LIKE '%s'
                            AND description LIKE '%s')
                            ORDER BY %s %s ", $cfg['tab']['mod'], $idClient, $this->_moduleType, '%' . $this->_filter . '%', $this->_orderBy, $this->_sortOrder);

        $db = cRegistry::getDb();
        $db->query($sql);
        $result = array();

        while (($module = $db->nextRecord()) !== false) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = array(
                    'name' => $db->f('name'),
                    'description' => $db->f('description'),
                    'error' => $db->f('error'),
                    'input' => $this->readInput(),
                    'output' => $this->readOutput()
            );
        }

        return $result;
    }

}
