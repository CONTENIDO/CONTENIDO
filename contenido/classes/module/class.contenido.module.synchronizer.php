<?php 

/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This class synchronized the contents of modul dir with the table
 * $cfg["tab"]["mod"]. If a modul exist in modul dir but not in
 * $cfg["tab"]["mod"] this class will insert the modul in the table.
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
 *   created 2010-12-14
 *
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("classes", "class.security.php");
cInclude("classes","contenido/class.module.php");
cInclude("includes","functions.api.string.php");

cInclude("classes","module/class.contenido.module.handler.php");
cInclude("includes", "functions.con.php");

class Contenido_Moudle_Synchronizer extends Contenido_Module_Handler {

   /**
    *The last id of the module that had changed or had added.  
    *
    * @var int 
    */
    private $_lastIdMod = 0;

   /**
    * 
    * This method insert a new modul in $cfg["tab"]["mod"] table, if
    * the name of modul dont exist
    * 
    * @param unknown_type $dir
    * @param unknown_type $oldModulName
    * @param unknown_type $newModulName
    * @param unknown_type $idclient
    */
     
    private function _addOrUpdateModul($dir , $oldModulName , $newModulName , $idclient) {
         
        #if modul dont exist in the $cfg["tab"]["mod"] table.
        if($this->_isExistInTable($oldModulName, $idclient) == false) {
			
             #add new Module in db-tablle     
             $this->_addModul($newModulName,$idclient);
             $notification = new Contenido_Notification();
             $notification->displayNotification('info', i18n("Synchronization successfully modul name: ").$newModulName);                 
          } else {
          	
          		#update the name of the module
          		if($oldModulName != $newModulName) {
          			$this->_updateModulnameInDb($oldModulName, $newModulName, $idclient);
          		}
          }                    
    }
    
    
   /**
    * 
    * Rename css, js and input/output file
    * @param unknown_type $dir
    * @param unknown_type $oldModulName
    * @param unknown_type $newModulName
    */
   private function _renameFiles($dir,$oldModulName,$newModulName) {
   		
   		if(file_exists($dir.$newModulName.'/'.$this->_directories['php'].$oldModulName.'_input.php') == true)
   			rename($dir.$newModulName.'/'.$this->_directories['php'].$oldModulName.'_input.php', $dir.$newModulName.'/'.$this->_directories['php'].$newModulName.'_input.php');
   			
   		if(file_exists($dir.$newModulName.'/'.$this->_directories['php'].$oldModulName.'_output.php') == true)
   			rename($dir.$newModulName.'/'.$this->_directories['php'].$oldModulName.'_output.php', $dir.$newModulName.'/'.$this->_directories['php'].$newModulName.'_output.php');
   			
   		if(file_exists($dir.$newModulName.'/'.$this->_directories['css'].$oldModulName.'.css') == true)
   			rename($dir.$newModulName.'/'.$this->_directories['css'].$oldModulName.'.css', $dir.$newModulName.'/'.$this->_directories['css'].$newModulName.'.css');
   		
   		if(file_exists($dir.$newModulName.'/'.$this->_directories['js'].$oldModulName.'.js') == true)
   			rename($dir.$newModulName.'/'.$this->_directories['js'].$oldModulName.'.js', $dir.$newModulName.'/'.$this->_directories['js'].$newModulName.'.js');
   }
    /**
     * 
     * Rename the Modul files and Modul dir 
     * 
     * @param string $dir path the the moduls
     * @param string $dirNameOld old dir name
     * @param string $dirNameNew new dir name
     * @param int $client idclient 
     * @throws Exception if we could not rename the old dir name
     */
    
    private function _renameFileAndDir($dir,$dirNameOld , $dirNameNew , $client ) {
        
        if( rename($dir.$dirNameOld, $dir.$dirNameNew) == FALSE ) 
            return false;
    
         else  {#change names of the files
              
                              
             $this->_renameFiles($dir, $dirNameOld, $dirNameNew);
             	
          }
        return true;
    } 
    
    
    /**
     * Compare file change timestemp and the timestemp in ["tab"]["mod"].
     * If file had changed make new code :conGenerateCodeForAllArtsUsingMod
     * 
     */
    public function compareFileAndModulTimestamp() {
        
    	$sql = sprintf("SELECT UNIX_TIMESTAMP(mod1.lastmodified) AS lastmodified,mod1.idclient,description,type, mod1.name, mod1.alias, mod1.idmod FROM %s AS mod1 WHERE mod1.idclient = %s", 
    					$this->_cfg['tab']['mod'], 
    					$this->_client);
     	$notification = new Contenido_Notification();
              				
    	$db = new DB_Contenido();
    	$db->query($sql);
    	$retIdMod = 0;
		
		global $cfgClient;
		
		
    	while($db->next_record()) {
    		$frontendPath = $cfgClient[$db->f('idclient')]['path']['frontend'];
			
    		$lastmodified = $db->f('lastmodified');
    		$lastmodInput  = filemtime($frontendPath.self::$MODUL_DIR_NAME.$db->f('alias')."/".$this->_directories['php'].$db->f('alias')."_input.php");
            $lastmodOutput = filemtime($frontendPath.self::$MODUL_DIR_NAME.$db->f('alias')."/".$this->_directories['php'].$db->f('alias')."_output.php");
    		
            if($lastmodInput < $lastmodOutput) {
            	#use output
            	if($lastmodified < $lastmodOutput) {
            		#update
            		$this->setLastModified($lastmodOutput,$db->f('idmod'));
                    conGenerateCodeForAllArtsUsingMod($db->f('idmod'));
                    #echo $db->f('name')." input <br/>";
                    $notification->displayNotification('info', i18n("Synchronization successfully modul name: ").$db->f('name'));
            	}
            		
            } else {
            	#use input
            	if($lastmodified < $lastmodInput) {
            		
            		#update
            		$this->setLastModified($lastmodInput,$db->f('idmod'));
                    conGenerateCodeForAllArtsUsingMod($db->f('idmod'));
                    #echo $db->f('name')." output <br/>";
                    $notification->displayNotification('info', i18n("Synchronization successfully modul name: ").$db->f('name'));
            	}
            }

            
          if( ($idmod = $this->_synchronizeFilesystemAndDb($db)) != 0)
          	$retIdMod = $idmod; 
            
    	}
    	
    	#we need it for the update of moduls on the left site (module/backend)
    	return $retIdMod;
    }
    
    
    
    /**
     * 
     * If someone delete a moduldir with ftp/ssh. We have a modul
     * in db but not in directory, if the modul in use make a new modul in fileystem but if not
     * clear it from filesystem. 
     * 
     */
    private function _synchronizeFilesystemAndDb($db) {
     	  
    	$returnIdMod = 0;
    	  $this->_initModulHandlerWithModulRow($db);
          $this->_echoIt("Modul wird gelesen....: ".$db->f("name"));
          #modul dont exist in filesystem
          if($this->existModul() == false) {
             $this->_echoIt("Modul existiert nicht: ".$db->f("name"));
              
             $modul = new cApiModule($db->f("idmod"));
             $returnIdMod = $db->f("idmod");
             if($modul->moduleInUse($db->f("idmod")) == true) {
                 #modul in use, make new modul in filesystem
                 if( $this->makenewModul()== false ) { 
                 	$notification = new Contenido_Notification();
             		$notification->displayNotification('info', i18n("Cant make modul: ").$db->f("name"));  
                 } else 
                 	$this->_echoIt("Modul angelegt: ".$db->f("name"));
                 
             } else {
                 
                 #modul not in use, delete it
                 $sql = sprintf("DELETE  FROM %s WHERE idmod = %s AND idclient = %s", $this->_cfg["tab"]["mod"], $db->f("idmod"),$this->_client);
                 $myDb = new DB_Contenido();
                 $myDb->query($sql);
                 $this->_echoIt("Modul gelöscht von Db: ".$db->f("name"));
                 
                 
             }
              
          }
          return $returnIdMod;
    }
    
   
    /**
     * 
     * If the first char a '.' return false else true
     * @param string $file
     * @return boolean true if the first char !='.' else false
     */
    private function _isValidFirstChar($file) {
    	
    	if(substr($file,0,1) == '.') {
    		return false;
    	}else 
    		return true;
    }
    /**
     * 
     * Depend from client,  this method
     * will check the modul dir of the client and if found
     * a Modul(Dir) that not exist in Db-table this method will
     * insert the Modul in Db-table ([tab][mod]).
     * 
     */
    public function synchronize() {
        global $cfgClient;
		$frontendPath = $cfgClient[$this->_client]['path']['frontend'];
           
            #get the path to the modul dir from the client
            $dir = $frontendPath.self::$MODUL_DIR_NAME; 
           
            if (is_dir($dir)) {                 
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {     
                       #is file a dir or not
                        if($this->_isValidFirstChar($file) && is_dir($dir.$file."/") ) {
                             
                        $newFile = capiStrCleanURLCharacters($file);
                        #dir is ok
                        if($newFile == $file) {
                            
                            $this->_addOrUpdateModul($dir , $file, $newFile , $this->_client); 
                                  
                        }else { #dir not ok (with not allowed characters)
                          
                            if(is_dir($dir.$newFile)) {# exist the new dir name?
                               
                            	
                                #make new dirname
                                $newDirName =$newFile.substr( md5( time() . rand(0 , time() )) , 0 , 4);
                                #rename 
                                if( $this->_renameFileAndDir($dir , $file , $newDirName , $this->_client) != false) {
                                    $this->_addOrUpdateModul($dir, $file, $newDirName, $this->_client);
                                }
                                
                                   
                            } else {#$newFile (dir) not exist
                               
                                #rename dir old
                                if( $this->_renameFileAndDir($dir, $file , $newFile , $this->_client) != false) {
                                	$this->_addOrUpdateModul($dir, $file, $newFile,$this->_client);
                                }
                                    
                               
                            }       
                           } 
                        } 
                       }
                    }
                    #close dir
                    closedir($dh);
                }
            
           
            #last Modul Id that will refresh the windows /modul overview
            return $this->_lastIdMod;
    }

     
    /**
     * This method look in the db-table $cfg["tab"]["mod"] for a modul
     * name. If the modul name exist it will return true
     * 
     * @param string $name name ot the modul
     * @param int $idclient idclient 
     * @return if a modul with the $name exist in the $cfg["tab"]["mod"] table return true else false
     */

    private  function _isExistInTable( $alias , $idclient) {

        $db = new DB_Contenido();
        
        
        #Select depending from idclient all moduls wiht the name $name
        $sql = sprintf("SELECT * FROM %s WHERE alias='%s' AND idclient=%s" , $this->_cfg["tab"]["mod"] , $alias ,$idclient);
        
        $db->query($sql);
        
        #a record is found
        if( $db->next_record())
            return true;
        else     
            return false;
        
    }
    
    
    /**
     * 
     * Update the name of module (if the name not allowes)
     * @param string $oldName  old name
     * @param string $newName new module name
     * @param int $idclient id of client
     */
    private function _updateModulnameInDb($oldName,$newName,$idclient) {
    	
    	$db = new DB_Contenido();
    	
    	#Select depending from idclient all moduls wiht the name $name
        $sql = sprintf("SELECT * FROM %s WHERE alias='%s' AND idclient=%s" , $this->_cfg["tab"]["mod"] , $oldName ,$idclient);
        
        $db->query($sql);
        
        #a record is found
        if( $db->next_record()) {
        	
        	$sqlUpdateName = sprintf("UPDATE %s SET alias='%s' WHERE idmod=%s", $this->_cfg["tab"]["mod"],$newName,$db->f('idmod'));
        	
        	$db->query($sqlUpdateName);
        	return ;
        }
           
            
    	
    }
    /**
     * 
     * This method add a new Modul in the table $cfg["tab"]["mod"].
     * 
     * @param string $name neme of the new modul
     * @param int $idclient  mandant of the modul
     */
    private  function _addModul($name , $idclient) {
        
       
        $db = new DB_Contenido();
        #get next id from $cfg["tab"]["mod"]
        $nextId = $db->nextid($this->_cfg["tab"]["mod"]);
        #insert new modul in con_mod
        $sql = sprintf(" INSERT INTO %s (name,alias,idclient,idmod) VALUES('%s','%s',%s,%s) ", $this->_cfg["tab"]["mod"], $name,$name, $idclient , $nextId);
      
        
        $db->query($sql);
        
        #save the last id from modul
       $this->_lastIdMod = $nextId;
 
    }
    
    
	/**
     * Update the con_mod, the field lastmodified
     * 
     * @param int $timestamp timestamp of last modification
     * @param int $idmod id of modul 
     */
    public function setLastModified($timestamp,$idmod) {

        $sql = sprintf("UPDATE %s SET lastmodified ='%s' WHERE idmod=%s ", $this->_cfg["tab"]["mod"],date("Y-m-d H:i:s",$timestamp),$idmod);
       
        $myDb = new DB_Contenido();
        $myDb->query($sql);
        
    }
    
}

?>