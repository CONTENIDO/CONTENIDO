<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Super class for revision
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release >= 4.8.8
 * 
 * {@internal 
 *   created 2008-08-12
 *
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
 die('Illegal call');
}
 
 class VersionImport extends Version {
	
	/**
	* The name of modul
	* @access private
	*/		
	private $sName;
	
	/**
	* Error Output
	* @access private
	*/		
	private $sError;
	
	/**
	* Description of modul
	* @access private
	*/		
	private $sDescripion;
	
	/**
	* Information of deletable
	* @access private
	*/		
	private $bDeletabel;
	
	/**
	* Code Input
	* @access private
	*/		
	private $sCodeInput;
	
	/**
	* Code Output
	* @access private
	*/		
	private $sCodeOutput; 
	
	/**
	* Template name of modul
	* @access public
	*/		
	public $sTemplate;
	
	/**
	* static
	* @access private
	*/		
	private $sStatic; 
	
	/**
	* Information about package guid
	* @access private
	*/		
	private $sPackageGuid;
	
	/**
	* Information of package data
	* @access private
	*/		
	private $sPackageData;
	
	/**
	* Type of modul
	* @access public
	*/		
	public $sModType;
	
	/**
	* Array contents all version information
	* @access public
	*/		
	private $aCreateVersion;
	
	/**
	* Check variable for look version number
	* @access public
	*/		
	private $iWert;
	
	/**
	* The class versionImport object constructor, initializes class variables
	* 
	* @param string  $iIdMod The name of style file
	* @param array $aCfg
	* @param array $aCfgClient
	* @param object $oDB
	* @param integer $iClient
	* @param object $sArea
	* @param object $iFrame
	* 
	* @return void its only initialize class members
	*/	
 	public function __construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame){
//		Set globals in main class
		parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
							
// 		folder layout
 		$this->sType = "module";

//		init class member 		 		
 		$this->aCreateVesion = array();
 		
//		Init class members with table con_history
 		$this->getModuleHistoryTable();

//		Sort the version files true
 		ksort($this->aCreateVersion);

// 		All array read
 		foreach( $this->aCreateVersion as $sKey=>$sLevelOne) {
 			foreach($sLevelOne as $sKey2=>$sLevelTwo) {
	 			parent::__construct($aCfg, $aCfgClient, $oDB, $sKey, $sArea, $iFrame);
 				foreach($sLevelTwo as $sKey3=>$sLevelThree) {
                    $this->iIdentity = $sKey2; 
					$this->sName = Contenido_Security::unFilter($sLevelThree["name"]);
					$this->sModType = Contenido_Security::unFilter($sLevelThree["type"]);
					$this->sError = Contenido_Security::unFilter($sLevelThree["error"]);
					$this->sDescripion = Contenido_Security::unFilter($sLevelThree["description"]);
					$this->sDeletabel = Contenido_Security::unFilter($sLevelThree ["deletable"]);
					$this->sCodeInput = Contenido_Security::unFilter($sLevelThree ["input"]);
					$this->sCodeOutput = Contenido_Security::unFilter($sLevelThree ["output"]);
					$this->sTemplate = Contenido_Security::unFilter($sLevelThree["template"]);
					$this->sStatic = Contenido_Security::unFilter($sLevelThree["static"]);
					$this->sPackageGuid = Contenido_Security::unFilter($sLevelThree["package_guid"]);
					$this->sPackageData = Contenido_Security::unFilter($sLevelThree["package_data"]);
					$this->sAuthor = Contenido_Security::unFilter($sLevelThree["changedby"]);
					$this->dCreated = Contenido_Security::unFilter($sLevelThree["created"]);
					$this->dLastModified = Contenido_Security::unFilter($sLevelThree["changed"]);
					$this->dActualTimestamp = Contenido_Security::unFilter($sLevelThree["changed"]);
					
					$this->initRevisions();
					
					$this->createBodyXML();
                    
 					$this->createNewVersion();
 				}
 			}
 		}
 	} // end of constructor
 	 	
 	/**
 	 * Function reads rows variables from table con_mod and init with the class members.
 	 * 
 	 * @return void 
 	 */
 	private function getModuleHistoryTable(){
     	if(!is_object($this->oDB))
     	 $this->oDB = new DB_Contenido;	
	
		$sSql = "";
		$sSql = "SELECT *
                FROM ". $this->aCfg["tab"]["mod_history"];
        $this->oDB->query($sSql);        

//		save mod_history in three dimension array 
        while($this->oDB->next_record()) {
         	$this->aCreateVersion[$this->oDB->f("idclient")][$this->oDB->f("idmod")][$this->oDB->f("idmodhistory")] = 
         	array("idmodhistory"=>$this->oDB->f("idmodhistory"), "idmod"=>$this->oDB->f("idmod"),
					"idclient"=>$this->oDB->f("idclient"), "name"=>$this->oDB->f("name"),
					"type"=>$this->oDB->f("type"), "description"=>$this->oDB->f("description"),
					"input"=>$this->oDB->f("input"), "output"=>$this->oDB->f("output"),
					"template"=>$this->oDB->f("template"), "changedby"=>$this->oDB->f("changedby"),
					"changed"=>$this->oDB->f("changed"));
         }        
    } // end of function
 	
 	/**
 	 * Set with the body nodes of xml file
 	 * 
 	 * @return void 
 	 */
 	private function createBodyXML() {
// 	 Create Body Node of Xml File
 		$this->setData("Name", $this->sName);
 		$this->setData("Modul_Type", $this->sModType);
 		$this->setData("Error", $this->sError);
 		$this->setData("Description", $this->sDescripion);
 		$this->setData("Deletable", $this->bDeletabel);
 		$this->setData("CodeInput", $this->sCodeInput);
 		$this->setData("CodeOutput", $this->sCodeOutput);
 		$this->setData("Template", $this->sTemplate);
 		$this->setData("Static", $this->sStatic);
 		$this->setData("PackageGuid", $this->sPackageGuid);
 		$this->setData("PackageData", $this->sPackageData);
 		
 	}
 	

} 
?>