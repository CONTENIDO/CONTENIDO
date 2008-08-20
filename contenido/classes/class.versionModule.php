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

 class VersionModule extends Version {
	
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
	* The class versionStyle object constructor, initializes class variables
	* 
	* @param string $iIdMod The name of style file
	* @param array  $aCfg
	* @param array  $aCfgClient
	* @param object $oDB
	* @param integer $iClient
	* @param object $sArea
	* @param object $iFrame
	* 
	* @return void its only initialize class members
	*/	
 	public function __construct($iIdMod, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame){
//		 Set globals in main class
		parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
							
// 		folder layout
 		$this->sType = "module";
 		
 		$this->iIdentity = $iIdMod;
        
        $this->prune();
        
 		$this->initRevisions();
 		
 		// Get Module Table Iformation
 		$this->getModuleTable();
 		
 		// Create Body Node of Xml File
 		$this->setData("Name", $this->sName);
 		$this->setData("Type", $this->sModType);
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
 	
 	/**
 	 * Function reads rows variables from table con_mod and init with the class members.
 	 * 
 	 * @return void  
 	 */
 	private function getModuleTable(){
     	
     	if(!is_object($this->oDB)) {
     	 $this->oDB = new DB_Contenido;	
     	}
		$sSql = "";
		$sSql = "SELECT *
                FROM ". $this->aCfg["tab"]["mod"] ."
                WHERE  idmod = '".Contenido_Security::toInteger($this->iIdentity)."'";
      
        if($this->oDB->query($sSql)) {
	        $this->oDB->next_record();
			$this->iClient = $this->oDB->f("idclient");
			$this->sName = $this->oDB->f("name");
			$this->sModType = $this->oDB->f("type");
			$this->sError = $this->oDB->f("error");
			$this->sDescripion = $this->oDB->f("description");
			$this->sDeletabel = $this->oDB->f("deletable");
			$this->sCodeInput = $this->oDB->f("input");
			$this->sCodeOutput = $this->oDB->f("output");
			$this->sTemplate = $this->oDB->f("template");
			$this->sStatic = $this->oDB->f("static");
			$this->sPackageGuid = $this->oDB->f("package_guid");
			$this->sPackageData = $this->oDB->f("package_data");
			$this->sAuthor = $this->oDB->f("author");
			$this->dCreated = $this->oDB->f("created");
			$this->dLastModified = $this->oDB->f("lastmodified");
        }

    } // end of function
	
   /**
	* This function read an xml file nodes
	* 
	* @param string $sPath Path to file
	* 
	* @return array returns array width this four nodes
	*/	
    public function initXmlReader($sPath) {
    	$aResult = array();
    	if($sPath !=""){
    			// Output this xml file
			$sXML = simplexml_load_file($sPath);
		
			if ($sXML) {
				foreach ($sXML->body as $oBodyValues) {
					//	if choose xml file read value an set it						
					$aResult["name"] = $oBodyValues->Name;
					$aResult["desc"] = $oBodyValues->Description;
					$aResult["code_input"] = $oBodyValues->CodeInput;
					$aResult["code_output"] = $oBodyValues->CodeOutput;
				}
				
			}
    	}
    	
    	return $aResult;
 	} // end of function
    
	/**
	 * Function returns javascript which refreshes contenido frames for file list an subnavigation.
	 * This is neccessary, if filenames where changed, when a history entry is restored
	 *
	 * @param integer $iIdClient id of client which contains this file
	 * @param string $sArea name of contenido area in which this procedure should be done
	 * @param integer $iIdLayout Id of layout to highlight
	 * @param object $sess Contenido session object
	 *
	 * @return string  - Javascript for refrehing frames
	 */
    public function renderReloadScript($sArea, $iIdModule, $sess) {
        $sReloadScript = "<script type=\"text/javascript\">
				 var left_bottom = top.content.left.left_bottom;
		
				 if(left_bottom){
                    var href = '".$sess->url("main.php?area=$sArea&frame=2&idmod=$iIdModule")."';
					left_bottom.location.href = href;
				 }
				 
                 </script>";
        return $sReloadScript;
    }
} 
?>