<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class of Layout Revision
 * We use super class Version to create a new Version.
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
 *   created 2008-08-05
 *
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
 die('Illegal call');
}

 class VersionLayout extends Version {
   /**
	* The name of Layout
	* @access private
	*/		
	private $sName;
	
   /**
	* The code of Layout
	* @access private
	*/	
 	private $sCode;
 	
   /**
	* The Description of Layout
	* @access private
	*/	
 	private $sDescripion;
 	
   /**
	* The Metainformation about layout
	* @access private
	*/	
 	private $sDeletabel;
 	
   /**
	* The class versionLayout object constructor, initializes class variables
	* 
	* @param string  $iIdLayout The name of style file
	* @param array $aCfg
	* @param array $aCfgClient
	* @param object $oDB
	* @param integer $iClient
	* @param object $sArea
	* @param object $iFrame
	* 
	* @return void its only initialize class members
	*/		
 	public function __construct($iIdLayout, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame){
//		 Init class members in super class
		parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
							
// 		folder layout
 		$this->sType = "layout";
 		$this->iIdentity = $iIdLayout;

//		This function looks if maximum number of stored versions is achieved		
        $this->prune();
 		
 		$this->initRevisions();
 		
 		// Set Layout Table Iformation
 		$this->setLayoutTable();
 		
 		// Create Body Node of Xml File
 		$this->setData("name", $this->sName);
 		$this->setData("description", $this->sDescripion);
 		$this->setData("code", $this->sCode);
 		$this->setData("deletable", $this->bDeletabel);
 
 	}
 	
 	/**
 	 * Function reads rows variables from table con_layout and init with the class members.
 	 * 
 	 * @return void 
 	 */
 	private function setLayoutTable(){

     	if(!is_object($this->oDB))
     	 $this->oDB = new DB_Contenido;	
     	 
		$sSql = "";
		$aLayout = array();
		
		$sSql = "SELECT
                    *
                FROM
                ". $this->aCfg["tab"]["lay"] ."
                WHERE
                    idlay = '".Contenido_Security::toInteger($this->iIdentity)."'";
       
        if($this->oDB->query($sSql)) {
	        $this->oDB->next_record();
			$this->iClient = $this->oDB->f("idclient");
			$this->sName = $this->oDB->f("name");
			$this->sDescripion = $this->oDB->f("description");
			$this->sDeletabel = $this->oDB->f("deletable");
			$this->sCode = $this->oDB->f("code");   	    	 
			$this->sAuthor = $this->oDB->f("author");
			$this->dCreated = $this->oDB->f("created");
			$this->dLastModified = $this->oDB->f("lastmodified");
        }

    } // end function
    
   /**
	* This function read an xml file nodes
	* 
	* @param string  $sPath Path to file
	* 
	* @return array returns array width this three nodes
	*/	
    public function initXmlReader($sPath) {
    	$aResult = array();
    	if($sPath !=""){
    			// Output this xml file
			$sXML = simplexml_load_file($sPath);
		
			if ($sXML) {
				foreach ($sXML->body as $oBodyValues) {
					//	if choose xml file read value an set it						
					$aResult["name"] = $oBodyValues->name;
					$aResult["desc"] = $oBodyValues->description;
					$aResult["code"] = $oBodyValues->code;
				}
			}
    	} 
    	return $aResult;
    }
 	
   /**
      * Function returns javascript which refreshes contenido frames for file list an subnavigation.
      * This is neccessary, if filenames where changed, when a history entry is restored
      *
      * @param integer $iIdClient - id of client which contains this file
      * @param string  $sArea - name of contenido area in which this procedure should be done
      * @param integer $iIdLayout - Id of layout to highlight
      * @param object $sess - Contenido session object
      *
      * @return string  - Javascript for refrehing frames
      */
    public function renderReloadScript($sArea, $iIdLayout, $sess) {
        $sReloadScript = "<script type=\"text/javascript\">
				 var left_bottom = top.content.left.left_bottom;
		
				 if(left_bottom){
                    var href = '".$sess->url("main.php?area=$sArea&frame=2&idlay=$iIdLayout")."';
					left_bottom.location.href = href;
				 }
				 
                 </script>";
        return $sReloadScript;
    }
	
 }// end of class
?>	
