<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class of File System
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
 
 class VersionFile extends Version {
	
    /**
	* Content code of current file.	 
	* @access public
	*/	
	public $sCode;
	
    /**
    * Description folder of history sub nav. 
    * Its not required to use it.
	* @access protected
	*/
	public $sDescripion;
	
	/**
	* The path of style file.
	* @access public
	*/	
	public $sPath;
	
	/**
	* The id of Type.
	* @access public
	*/		
	public $sFileName;
	
   /**
	* The class versionStyle object constructor, initializes class variables
	* 
	* @param string  $iIdOfType The name of style file
	* @param array  $aFileInfo Get FileInformation from table file_information
	* @param array $aCfg
	* @param array $aCfgClient
	* @param object $oDB
	* @param integer $iClient
	* @param object $sArea
	* @param object $iFrame
	* 
	* @return void its only initialize class members
	*/			 		
   	public function __construct($iIdOfType, $aFileInfo, $sFileName, $sTypeContent, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame, $sVersionFileName = '') {
//		Set globals in super class constructer
		parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);
							
// 		Folder name is css or js ...
 		$this->sType = $sTypeContent;
 		
// 		File Name for xml node
		$this->sFileName = $sFileName;
		
// 		File Information, set for class Version to generate head xml nodes
		$this->sDescripion = $aFileInfo["description"];
		$this->sAuthor = $aFileInfo["author"];
		$this->dLastModified = $aFileInfo["lastmodified"];
		$this->dCreated = $aFileInfo["created"];
		
// 		Frontendpath to files
		if($sTypeContent == "templates"){
			$sTypeContent = "tpl";
		}
 	
        $this->sPath = $this->aCfgClient[$this->iClient][$sTypeContent]["path"];
 		
// 		Identity the Id of Content Type
 		$this->iIdentity = $iIdOfType;
		
//		This function looks if maximum number of stored versions is achieved		
        $this->prune();
		
//		Take revision files if exists 		
 		$this->initRevisions();
 		
//		Get code of style 		
 		$this->initFileContent();
 		
// 		Set Layout Table Iformation, currently not in use!
 		#$this->setLayoutTable();
        
        if ($sVersionFileName == '') {
            $sVersionFileName = $this->sFileName;
        }
        
// 		Create Body Node of Xml File
 		$this->setData("name", $sVersionFileName);
 		$this->setData("code", $this->sCode);
 		$this->setData("description", $this->sDescripion);
 	}
    
   /**
	* This function init the class member sCode with current file content
	* 
	* @return void only init sCode
	*/	
    protected function initFileContent() {
	    if (file_exists($this->sPath.$this->sFileName)) {
	    if (!$handle = fopen( $this->sPath.$this->sFileName, "rb")) {
	    print i18n("Can not open file "). $this->sPath.$this->sFileName;
	        return;
	    } do {
	          $_data = fread($handle, 4096);
	          if (strlen($_data) == 0) {
	              break;
	          }
	           $this->sCode .=  $_data;
	      } while(true);
	     fclose($handle);
    	}else{
	      	echo "<br>File not exists " . $this->sPath.$this->sFileName;
	    } 
    }
     
   
  /**
   * This function read an xml file nodes
   * 
   * @param string  $sPath Path to file
   * 
   * @return {array} returns array width nodes
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
	* This function reads the path of file 
	*  
	* @param string  $sPath Path to file
	* 
	* @return string  the path of file
	*/	
    public function getPathFile() {
    	return $this->sPath;
    }
    
	/**
	 * Function returns javascript which refreshes contenido frames for file list an subnavigation.
	 * This is neccessary, if filenames where changed, when a history entry is restored
	 *
	 * @param integer $iIdClient - id of client which contains this file
	 * @param string  $sArea - name of contenido area in which this procedure should be done
	 * @param string  $sFilename - new filename of file which should be updated in other frames
	 * @param object $sess - Contenido session object
	 *
	 * @return string  - Javascript for refrehing frames
	 */
    public function renderReloadScript($sArea, $sFilename, $sess) {
        $sReloadScript = "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
				 var left_bottom = top.content.left.left_bottom;

                 if (right_top) {
                     var href = '".$sess->url("main.php?area=$sArea&frame=3&file=$sFilename&history=true")."';
                     right_top.location.href = href;
                 }
		
				 if(left_bottom){
                    var href = '".$sess->url("main.php?area=$sArea&frame=2&file=$sFilename")."';
					left_bottom.location.href = href;
				 }
				 
                 </script>";
        return $sReloadScript;
    }

 } // end of class
?>