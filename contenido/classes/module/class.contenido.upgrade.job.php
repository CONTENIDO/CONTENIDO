<?php 

/**
 * Project:
 * CONTENIDO Content Management System
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
 * @package    CONTENIDO Backend Classes
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
 
if(file_exists(dirname(__FILE__)."/../../includes/functions.api.string.php"))
    include_once(dirname(__FILE__)."/../../includes/functions.api.string.php");        

if(file_exists(dirname(__FILE__)."/class.contenido.module.base.php"))
    include_once(dirname(__FILE__)."/class.contenido.module.base.php");
     
        
 if(file_exists(dirname(__FILE__)."/class.contenido.module.handler.php"))
    include_once(dirname(__FILE__)."/class.contenido.module.handler.php");
     
 if(file_exists(dirname(__FILE__)."/class.contenido.translate.from.file.php"))
    include_once(dirname(__FILE__)."/class.contenido.translate.from.file.php");
         

    class Contenido_UpgradeJob extends Contenido_Module_Handler {
        protected $_db = null;
    	
        public function __construct($db) {
			$this->_db = $db;
           
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
            $myDb = clone $this->_db;
            $db = clone $this->_db;
            
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
            
           
            $myDb = clone $this->_db;
            $db = clone $this->_db;
            
            $changeDb = clone $this->_db;
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
        public function saveAllModulsToTheFile($setuptype) {
			$db = clone $this->_db;
        	
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
                   $dbInput = clone $this->_db;
                   $sqlInput = sprintf("UPDATE %s SET input='%s' WHERE idmod=%s AND idclient=%s",$this->_cfg["tab"]["mod"],"", $idmod ,$this->_client);
                   //$dbInput->query($sqlInput);
                
                   $dbOutput = clone $this->_db;
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
            
                   
    }
    
?>