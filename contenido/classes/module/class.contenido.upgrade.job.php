

<?php 

/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * This class  transfers the old modul-system in the
 * new modul-system. Also from Db-table oriented structure
 * to the file-system orinted structure.
 * 
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since
 *
 * {@internal
 *   created 2010-12-22
 *
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}   

if(file_exists(dirname(__FILE__)."/../class.security.php"))
    include_once(dirname(__FILE__)."/../class.security.php");
 
        

if(file_exists(dirname(__FILE__)."/class.contenido.module.base.php"))
    include_once(dirname(__FILE__)."/class.contenido.module.base.php");
     
        
 if(file_exists(dirname(__FILE__)."/class.contenido.module.handler.php"))
    include_once(dirname(__FILE__)."/class.contenido.module.handler.php");
     
 if(file_exists(dirname(__FILE__)."/class.contenido.translate.from.file.php"))
    include_once(dirname(__FILE__)."/class.contenido.translate.from.file.php");
         

    class Contenido_UpgradeJob extends Contenido_Module_Handler {
        
    	
        public function __construct() {
           
        	$this->_debug = false;
            try {
            parent::__construct();
            } catch(Exception $e) {
                $this->errorLog($e->getMessage());
            } 
        }

    
        
    
    /**
     * This method clean the name of moduls table $cfg["tab"]["mod"].
     * Clean means all the charecters (�,*+#...) will be replaced.
     * 
     */
        private  function _changeNameCleanURL() {
              
            $myDb = new DB_Contenido();
            $db = new DB_Contenido();
            
            #select all moduls 
            $sql = sprintf("SELECT * FROM %s",$this->_cfg["tab"]["mod"]);
            $db->query($sql);
            
            while($db->next_record()) {
                
                #clear name  from not allow charecters
                $newName = capiStrCleanURLCharacters($db->f("name"));
                if($newName != $db->f("name") ) {
                    
                    $mySql = sprintf("UPDATE %s SET name='%s' WHERE idmod=%s",$this->_cfg["tab"]["mod"],$newName,$db->f("idmod"));
                    
                   $myDb->query($mySql);
                }    
            }   
        }
        
        /**
         *This method change the name in $cfg["tab"]["mod"] table if the
         *same name will be found more than one time.
         *
         */
        private function _changeIsSameName() {
            
           
            $myDb = new DB_Contenido();
            $db = new DB_Contenido();
            
            $changeDb = new DB_Contenido();
            #get all moduls
            $sql = sprintf("SELECT * FROM %s ",$this->_cfg["tab"]["mod"]);
            $db->query($sql);
            
            while($db->next_record()) {
                
                #get all moduls from client
                $mySql = sprintf("SELECT * FROM %s WHERE name='%s' AND idmod != %s AND idclient=%s",$this->_cfg["tab"]["mod"],$db->f("name"),$db->f("idmod"),$db->f("idclient"));
                $myDb->query($mySql);
                
                while($myDb->next_record()) {
                   
                    $microtime = microtime();
                    $zufall = rand(0,time());
                    #make name uneque
                    $unique =  substr(md5($microtime.$zufall),0,5);
                    #update name with the new uneque name
                    $changeSql = sprintf("UPDATE %s SET name='%s' WHERE idmod=%s",$this->_cfg["tab"]["mod"],$myDb->f("name")."_".$unique,$myDb->f("idmod"));
                   
                    $changeDb->query($changeSql);  
                   
                } 
            }   
            
            
        }
        
        
        
        /**
         * 
         * This method will be transfer the moduls from $cfg["tab"]["mod"] to the
         * file system. This Method will be call by setup
         * 
         * 
         */
        public function saveAllModulsToTheFile($setuptype,$db) {
        	
        	#clean name oft module (Umlaute, not allowed character ..), prepare for file system
        	$this->_changeNameCleanURL();
        	$result = "";
            #select all frontendpaht of the clients, frontendpaht is in  the table $cfg["tab"]["clients"]
            $sql = sprintf("SELECT clients.frontendpath as frontendpath ,modul.description as description, modul.type as type,modul.alias as alias, modul.name as name, modul.output as output, modul.input as input, modul.idmod as idmod , clients.idclient as idclient  FROM %s AS clients , %s AS modul WHERE clients.idclient = modul.idclient ORDER BY modul.idmod ", $this->_cfg["tab"]["clients"] , $this->_cfg["tab"]["mod"]); 
            $this->_echoIt("sql saveAllModuls....:".$sql);
            $db->query($sql);
         
            #make in all clients(frontendspaths) modul directory
            $this->makeModulMainDirectories();
           
            while($db->next_record()) {
  				
            	$this->_echoIt($db->f('idmod'));
                # init the ModulHandler with all data of the modul
                # inclusive client
                $this->_initModulHandlerWithModulRow($db);
 				
               if($setuptype == "setup") {
               	$this->_echoIt('setup');
                #save the modul from db to the filesystem
                if( $this->_makeAndSaveModul($db->f("input") , $db->f("output"),$db->f("idmod")) == false )
                    $this->errorLog(sprintf('Cant make a new modul! Modul name :"%s" , idclient: %s ',$db->f('name'),$db->f('idclient')));
				else {
                    #save translation
                    $translations = new Contenido_Translate_From_File($db->f("idmod"));
                    $translations->saveTranslations();
                    $newOutput = $this->saveTemplateContent($db->f("frontendpath"),$db->f("output"));
                    $this->saveOutput($newOutput);
					}
                    
                } elseif($setuptype == "upgrade") {
                	$this->_echoIt("upgrade");
                    #make new module only if modul not exist in directory
                    if($this->existModul() != true) { 
                       if( $this->_makeAndSaveModul($db->f("input") , $db->f("output"),$db->f("idmod")) == false )
                         $this->errorLog(sprintf('Cant make a new modul! Modul name :"%s" , idclient: %s ',$db->f('name'),$db->f('idclient'))); 
                       else {
                        #save translation 
                    	$translations = new Contenido_Translate_From_File($db->f("idmod"));
                    	$translations->saveTranslations();
                    	
                    	$newOutput = $this->saveTemplateContent($db->f("frontendpath"),$db->f("output"));
                    	$this->saveOutput($newOutput);
                       }
                    	
                    }
                }
                }
                
                
                #remove input and output fields from db
                $sql = sprintf("ALTER TABLE %s DROP input, DROP output", $this->_cfg["tab"]["mod"]);
                $db->query($sql);
            }  
        
         /**
     * 
     * This method will make a new modul in filesystem and
     * save the contents of the fields (input, output) from  $cfg["tab"]["mod"] table 
     * in a file. 
     * In finale version this method will set the fields (input and output) to "";
     */
    
    private function _makeAndSaveModul($input , $output , $idmod) {
       
            if( $this->makeNewModul($input,$output) == false) 
                return false;
            else {
                //$this->saveInput($input);
                //$this->saveOutput($output);
               $this->_echoIt("Modul gespeichert ...vergleich");
               #if all right saved in files set input and output in db to "" 
               if( $input == $this->readInput() && $output == $this->readOutput()) {
                   $dbInput = new DB_Contenido();
                   $sqlInput = sprintf("UPDATE %s SET input='%s' WHERE idmod=%s AND idclient=%s",$this->_cfg["tab"]["mod"],"", $idmod ,$this->_client);
                   //$dbInput->query($sqlInput);
                
                   $dbOutput = new DB_Contenido();
                   $sqlOutput = sprintf("UPDATE %s SET output='%s' WHERE idmod=%s AND idclient=%s",$this->_cfg["tab"]["mod"],"", $idmod , $this->_client);
                   //$dbOutput->query($sqlOutput);
                   $this->_echoIt("Lösche input und output in table");
                   return true;
               } else {

               	$this->_echoIt("out:".$output);
               	$this->_echoIt("inp:".$input);
               	$this->_echoIt("input:". $this->readInput());
               	$this->_echoIt("output:". $this->readOutput());
               		$this->_echoIt("Input und Output des moduls nicht gleich");
                   return false;
               }
                   
            } 
            return true;    
    }
            
	
    /**
     * 
     * This method save the result from extract/copy of templates in modul directory
     * in a html document.
     * 
     * @param array $result
     */
    private function _saveResultInTable($result) {
	
	$templateReport = "template_report.html";
	$pastString = "<!-- PASTE -->";	

	#make file
	if(!is_file($templateReport)) {
		$tableAndHeader = "";
		$tableAndHeader = '<table style="border:1px solid black;">';
		$tableAndHeader .= "<tr>";
		$tableAndHeader .= "<th style=\"background-color:gray;\"> Modulname </th>";
		#add header of table
		foreach($result as $key=>$value )
		{
				$tableAndHeader.= "<th style=\"background-color:gray;\" >" .$key. "</th>"; 		
		}
		$tableAndHeader .= "</tr>";
		#past string for other rows
		$tableAndHeader .= $pastString;
		$tableAndHeader .= "</table>";
		
		$htmlDocument ='<?xml version="1.0" ?>
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
				<title>Template extract report</title>
				</head>
				<body>
				<h1>Template copy/extract report</h1>
				'. $tableAndHeader
		
						.'	
				</body>
				</html>
				';
		#save table in html
		file_put_contents($templateReport, $htmlDocument); 
	}
		
	$fileContent = file_get_contents($templateReport);
	$rows= "";
	$styleRed= "style=\"background-color:#FF4B4B;\"";
	
	$styleGreen= "style=\"background-color:#A8FF4B;\"";
	
		foreach($result['function_generate'] as $key=>$value ) {
		
			if($result['file_name'][$key] == "-" || $result['copy'][$key] == 0)
				$rows.= "<tr $styleRed>";
			else 
				$rows.= "<tr $styleGreen>";
				
				$rows.= "<td>" . $this->_modulName."</td>";
				$rows.= "<td>" .$value. "</td>"; 
				#$rows.= "<td>" .$result['without_function'][$key]. "</td>"; 
				$rows.= "<td>" .$result['template_acept'][$key]. "</td>"; 
				$rows.= "<td>" .$result['file_name'][$key]. "</td>";
				$rows.= "<td >" .$result['copy'][$key]. "</td>";	
				
				$rows.= "</tr>";
		}

		#paste rows
		$fileContent = str_replace($pastString, $rows.$pastString , $fileContent);
		
		#save new rows
		file_put_contents($templateReport , $fileContent);
	}
	
	
    
    /**
     * 
     * Save a template from frontendpath/template/filename.html to module path.
     * 
     * @param string $frontendpath 
     * @param string $output 
     * @return string $output without 'template/' in method generate
     */
  public function saveTemplateContent($frontendpath , $output) {

    	#get All template from $output
    	$allTemplates = $this->_getTemplateFilenameFromOutput($output);
    	
    	
    	if(count($allTemplates['file_name'])>0)	
    	foreach( $allTemplates['file_name'] as $key=>$value) {
    		#path and file 
    		$file = $frontendpath.'templates/'.$value;
    		#exist the file
    		if(is_file($file)) {
				
    			#get the contents of the file 
    			$content = file_get_contents($file);
    			#translate to from encoding to fileencoding(utf-8)
    			$content = iconv($this->_encoding,$this->_fileEncoding."//IGNORE",$content ); 
    			#save the content of file in modul/template/ directory
    			file_put_contents($this->getTemplatePath($value), $content);
				
    			#compare the contents of both files
    			if($content == file_get_contents($this->getTemplatePath($value))) {
    				#erase the file in old standard template directory
    				#unlink($file);
    				$output = str_replace('templates/'.$value,$value,$output);
    				$allTemplates ['copy'][$key] = 1;
    			}else {
    				$allTemplates ['copy'][$key] = 0;
    			}
    			
			}else {
				
				$allTemplates ['copy'][$key] = 0;
			}
		}
		if(count($allTemplates['file_name'])> 0) {
			$this->_saveResultInTable($allTemplates);
		}
    		
		return $output;
	}
	
	
	/**
	 * Search in $output for ->generate(...), try to extract the template file name.
	 * 
	 * 
	 * @param string $output
	 * @return array with the template names
	 */
	private function _getTemplateFilenamefromOutput($output) {
		
		$result = array();
		#serach for -> generate( ...);
		$suchmuster = "/\-\> *generate *\(([^\;]*)\) *;/";
		$anzahl = preg_match_all($suchmuster , $output , $treffer);
		
		#
		if(count($treffer[0])>0)
			$result['function_generate']  = $treffer[0];
		
		
		#if(count($treffer[1])>0)
			#$result['without_function']  = $treffer[1];
			
		#serach for /filename.html
		$suchmuster = "/^ *['|\"]{1}templates\/([_a-zA-Z0-9]{1,}\.html)['|\"]{1}.*$/";
		if(count($treffer[1])> 0)
		foreach ($treffer[1] as $key=>$value) {
			$anzahl = preg_match_all($suchmuster,$value,$myTreffer);
			
			//gefunden
			if($anzahl > 0) {
				
				$result['template_acept'] [$key] = 1;
				$result['file_name'] [$key] = $myTreffer[1][0];
			}else  {
				$result['template_acept'] [$key] = 0;
				$result['file_name'] [$key] = "-";
			}
		}
		
		return $result;
	}
    


    }
    
?>