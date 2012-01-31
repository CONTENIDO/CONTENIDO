<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * This class search for modules (input, output,type,description, name )
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release >= 4.9
 * 
 * {@internal 
 *   created 2012-01-31

 * }}
 * 
 */

/**
 * 
 * This class search for modules (input, output,type,description, name )
 * @author rusmir.jusufovic
 *
 */
class Contenido_Module_Search extends Contenido_Module_Handler {

	/**
	 *
	 * Items/Element per page
	 * @var int
	 */
	protected $_elementPerPage = '';
	/**
	 * 
	 * Order by 
	 * @var string
	 */
	protected $_orderBy = '';
	/**
	 * 
	 * asc,desc
	 * @var string
	 */
	protected $_sortOrder = '';
	/**
	 * 
	 * Wiche module type (Navigation,Content ...)
	 * @var string
	 */
	protected $_moduleType = '';
	
	/**
	 * 
	 * Filter string
	 * @var string
	 */
	protected $_filter = '';
	/**
	 * 
	 * Where should be searched (all, description, type, input, output)
	 * @var string
	 */
	protected $_searchIn = '';
	/**
	 * 
	 * Selected page
	 * @var int
	 */
	protected $_selectedPage = 1;

	/**
	 * 
	 * Result saved in a array
	 * @var array
	 */
	protected $_result = array();

	
	/**
	 * 
	 * Print a array
	 * @param array $arg
	 */
	private function _echo($arg) {
			
		echo "<pre>".print_r($arg)."</pre>";
			
	}
	
	
	public function __construct($searchOptions) {
		$this->_elementPerPage = $searchOptions['elementPerPage'];
		$this->_orderBy =  $searchOptions['orderBy'];
		$this->_sortOrder = $searchOptions['sortOrder'];
		$this->_moduleType =  $searchOptions['moduleType'];
		$this->_filter = $searchOptions['filter'];
		$this->_searchIn =  $searchOptions['searchIn'];
		$this->_selectedPage = $searchOptions['selectedPage'];
	}

	/**
	 * 
	 * Count result
	 * 
	 * @return int count in result
	 */
	public function getModulCount() {
			
		return count($this->_result);
	}
	
	/**
	 * Search for modules in db columns and in filesystem (input and output files)
	 * @return array result
	 */
	public function searchForAllModules() {
			
		$cfg = Contenido_Vars::getVar('cfg');
		$idClient = Contenido_Vars::getVar('client');

		$sql1 = sprintf("(SELECT *, (0) AS search_in_file FROM %s WHERE idclient = %s AND (
							type LIKE '%s' 
							AND type LIKE '%s' 
							OR description LIKE '%s' 
							OR name LIKE '%s')) ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType, '%'.$this->_filter.'%', '%'.$this->_filter.'%', '%'.$this->_filter.'%');
			
			
			
		$sql2 = sprintf("UNION (SELECT *, (1) AS search_in_file FROM %s WHERE idclient = %s AND NOT(
							type LIKE '%s' 
							AND type LIKE '%s' 
							OR description LIKE '%s' 
							OR name LIKE '%s'))
							ORDER BY %s %s ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType, '%'.$this->_filter.'%', '%'.$this->_filter.'%', '%'.$this->_filter.'%', $this->_orderBy, $this->_sortOrder);
			

			
		$db = new DB_Contenido();
			
		$db->query($sql1 .$sql2);
			
		$result = array();
			
		while(($modul = $db->next_record())) {

			if($db->f('search_in_file') == 1) {
				if($this->_findInFiles($this->_filter, $modul)== true) {
					$this->_initModulHandlerWithModulRow($db);
					$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());
						
				}
			}else  {
				$this->_initModulHandlerWithModulRow($db);
				$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());
			}
				
		}
			
		return $result;
			
	}

	/**
	 * 
	 * Search for a sting in input and output of module
	 * @param string $filter
	 * @param  (db object with the row) $dbRowModule
	 */
	private function _findInFiles($filter, $dbRowModule) {
			
		$this->_initModulHandlerWithModulRow($dbRowModule);
		if(stripos($this->readInput()." ". $this->readOutput(),$filter) === false)
		return false;
		else
		return true;
	}
	
	
	
	/**
	 * Main method for the class. Search for modules in db and in input and outputs files. 
	 * @return array result
	 */
	public function getModules() {
			
		$modules = array();
			
		switch($this->_searchIn) {

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
				$modules =  $this->findModuleWithType();
				break;
			case 'input'://Search for modulname_input.php
				$modules = $this->findModulWithInput();
				break;
			case 'output'://Search fro modulname_output.php
				$modules = $this->findModulWithOutput();
				break;
		}
			
		$this->_result = $modules;
		if($this->_elementPerPage>0) {

			if(count($this->_result)<(($this->_page-1)*$this->_elementPerPage))
			$this->_page = 1;

			if ($this->_elementPerPage*($this->_page) >= count($this->_result)+$this->_elementPerPage && $this->_page  != 1) {
				$this->_page--;
			}

			return array_slice($modules, $this->_elementPerPage *($this->_selectedPage-1),$this->_elementPerPage);
		}else  {
			return $modules;
		}		
	}
	
	/**
	 * Search for modules in "name" column of modul
	 * @return array result
	 */
	public function findeModulWithName() {


		$cfg = Contenido_Vars::getVar('cfg');
		$idClient = Contenido_Vars::getVar('client');


		$sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
							type LIKE '%s' AND name LIKE '%s' )
							ORDER BY %s %s ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType,'%'.$this->_filter.'%', $this->_orderBy, $this->_sortOrder);
			
			
		$db = new DB_Contenido();
		$db->query($sql);
		$result = array();
			
		while(($module = $db->next_record())) {
			$this->_initModulHandlerWithModulRow($db);
			$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());
		}
		return $result;
			
	}
	
	
	/**
	 * Search for modules in input file of the module
	 * @return array result
	 */
	public function findModulWithInput() {
			
			
		$cfg = Contenido_Vars::getVar('cfg');
		$idClient = Contenido_Vars::getVar('client');


		$sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
							type LIKE '%s' )
							ORDER BY %s %s ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType, $this->_orderBy, $this->_sortOrder);
			
			
		$db = new DB_Contenido();
		$db->query($sql);
		$result = array();
			
		while(($module = $db->next_record())) {
			$this->_initModulHandlerWithModulRow($db);

			if(stripos($this->readInput(),$this->_filter) !== false) {
				$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());
			}
				
		}
		return $result;
	}

	/**
	 * Search for modules in output of the module
	 * @return array result
	 */
	public function findModulWithOutput() {
			
		$cfg = Contenido_Vars::getVar('cfg');
		$idClient = Contenido_Vars::getVar('client');


		$sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
							type LIKE '%s' )
							ORDER BY %s %s ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType, $this->_orderBy, $this->_sortOrder);
			
			
		$db = new DB_Contenido();
		$db->query($sql);
		$result = array();
			
		while(($module = $db->next_record())) {
			$this->_initModulHandlerWithModulRow($db);

			if(stripos($this->readOutput(), $this->_filter) !== false) {
				$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());
			}
				
		}
		return $result;
	}

	
	/**
	 * 
	 *Search for modules in type column
	 *@return array result
	 */
	public function findModuleWithType() {
			
		$cfg = Contenido_Vars::getVar('cfg');
		$idClient = Contenido_Vars::getVar('client');


		$sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
							type LIKE '%s' 
							AND type LIKE '%s')
							ORDER BY %s %s ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType, '%'.$this->_filter.'%', $this->_orderBy, $this->_sortOrder);
			

		$db = new DB_Contenido();
		$db->query($sql);
		$result = array();
			
		while(($module = $db->next_record())) {
			$this->_initModulHandlerWithModulRow($db);
			$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());
		}
			
		return $result;

	}

	/**
	 * Search for modules in description column of modules
	 * @return array result
	 */
	public function findModuleWithDescription() {
			
		$cfg = Contenido_Vars::getVar('cfg');
		$idClient = Contenido_Vars::getVar('client');


		$sql = sprintf("SELECT * FROM %s WHERE idclient = %s AND (
							type LIKE '%s' 
							AND description LIKE '%s')
							ORDER BY %s %s ",
		$cfg['tab']['mod'], $idClient,  $this->_moduleType, '%'.$this->_filter.'%', $this->_orderBy, $this->_sortOrder);
			
		$db = new DB_Contenido();
		$db->query($sql);
		$result = array();
			
		while(($module = $db->next_record())) {
			$this->_initModulHandlerWithModulRow($db);
			$result[$db->f('idmod')] =array('name'=> $db->f('name'),
														'description'=>$db->f('description'), 
														'error'=>$db->f('error'),
														'input'=>$this->readInput(),
														'output'=>$this->readOutput());		
		}
		return $result;
			
			
	}




}

?>