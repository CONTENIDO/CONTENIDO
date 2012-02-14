<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for new modul structere. Saves the Modul-Input in a file (input.php) and
 * Modul-Output in a file(output.php). 
 * All moduls of a clients are in [frontend]/modules/.
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
 * created 2010-12-14
 *
 * }}
 *
 */

if (! defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * 
 * Class for new modul structere. Saves the Modul-Input in a file (input.php) and
 * Modul-Output in a file(output.php). 
 * All moduls of a clients are in [frontend]/modules/.
 * @author rusmir.jusufovic
 *
 */
class Contenido_Module_Handler {

    /**
     * 
     * Path to a modul dir 
     * 
     * @var string
     */
    private $_modulPath;

    /**
     * 
     * Path to the modul dir where are all the moduls of a client (frontendpath)
     * @var string
     */
    private $_path;

    /**
     * 
     * Id of the Modul
     * @var int
     */
    protected $_idmod = NULL;

    /**
     * 
     * The name of the modul
     * @var string
     */
    private $_modulName = NULL;

    /**
     * 
     * Description of the modul.
     * @var string
     */
    protected $_description;

    /**
     * 
     * The type of the modul.
     * @var string
     */
    protected $_type;

    /**
     * 
     * The aliac name of the modul
     * @var string
     */
    protected $_modulAlias;

    /**
     * 
     * display debug message if debug = true
     * @var bool
     */
    protected $_debug = false;

    /**
     *
     * Whats the name of modul dir where all moduls are.
     * @var string
     */
    static $MODUL_DIR_NAME = "modules/";

    /**
     * 
     * Name of the Info xml file.
     * 
     * @var string
     */
    
    static $NAME_OF_INFO_XML = "info.xml";

    /**
     * The names of the modul directories. 
     * 
     * @var array
     */
    protected $_directories = array(
        'css' => 'css/', 'js' => 'js/', 'template' => 'template/', 'image' => 'image/', 'lang' => 'lang/', 
        'php' => 'php/'
    );

    /**
     * 
     * CONTENIDO cfg
     * 
     * @var array
     */
    protected $_cfg = NULL;

    /**
     * 
     * Contenido cfgClient
     * @var array
     */
    protected $_cfgClient = NULL;

    /**
     * 
     * id of the Client
     * 
     * @var int
     */
    
    protected $_client = '0';

    /**
     * 
     * The code of the modul input
     * 
     * @var string
     */
    protected $_input = "";

    /**
     * 
     * The code of the modul output
     * 
     * @var string
     */
    protected $_output = "";

    /**
     * 
     * Encoding oft the site
     * @var string
     */
    protected $_encoding = "";

    /**
     * 
     * Which format of encoding should be files (input/output/translation...)
     * getEffectiveSetting('encoding', 'file_encoding','UTF-8')
     * @var string
     */
    protected $_fileEncoding = "";

    /**
     * 
     * The id of the lang
     * @var int
     */
    protected $_idlang = - 1;

    /**
     * @var DB_Contenido
     */
    private $_db = NULL;

    /**
     * 
     * Construct for the class Contenido_Module_Handler. With this class you can 
     * make a new Modul, rename a Modul. You can save a Output from Modul and Input in a
     * file. The save rules are [Modulname] (is uneque) the files input and output will be named
     * [Modulname]_input.php , [Modulname]_output.php
     *
     * @param Array $cfg  cfg
     * @param int $client the id of the client/mandant
     * @param int $idmod the id of the modul
     */
    
    public function __construct($idmod = NULL) {
        rereadClients();
        //get vars from Contenido_Vars
        $this->_cfg = Contenido_Vars::getVar('cfg');
        $this->_client = Contenido_Vars::getVar('client');
        $this->_cfgClient = Contenido_Vars::getVar('cfgClient');
        $this->_encoding = Contenido_Vars::getVar('encoding');
        $this->_idlang = Contenido_Vars::getVar('lang');
        
        $this->_fileEncoding = Contenido_Vars::getVar('fileEncoding');
        
        $this->_db = new DB_Contenido();
        
        $this->_idmod = $idmod;
        
        $this->_initModulHandlerFromDb($idmod);
        
        if ($this->_makeModulDirectory()== false) {
            
            $this->errorLog(" Cant make the main modul directory, class Contenido_Module_Handler !");
        }
    
    }

    /**
     * 
     * Exist the modulname in directory
     * 
     * @param string $name
     * @param array $cfgClient
     */
    static function existModulInDirectory($name, $cfgClient) {
        
        if (is_dir($cfgClient[Contenido_Vars::getVar('client')]['path']['frontend']. self::$MODUL_DIR_NAME. $name. '/')) {
            return true;
        } else
            return false;
    
    }

    /**
     * 
     * Save a content in the file, use for css/js
     * 
     * @param unknown_type $frontendPath
     * @param unknown_type $templateName
     * @param unknown_type $fileType
     * @param unknown_type $fileContent
     * @return false or string 
     */
    static function saveContentToFile($cfgClientData, $templateName, $fileType, $fileContent, $saveDirectory = "cache") {
        
        if (is_dir($cfgClientData["path"]["frontend"]. $saveDirectory. '/')) {
            
            //$fileContent = iconv(Contenido_Vars::getVar('encoding') , Contenido_Vars::getVar('fileEncoding'),$fileContent );
            if (file_put_contents(
            $cfgClientData["path"]["frontend"]. $saveDirectory. '/'. $templateName. ".". $fileType, $fileContent)=== false)
                return false;
        } else
            return false;
        
        return $cfgClientData["path"]["htmlpath"]. $saveDirectory. '/'. $templateName. ".". $fileType;
    }

    /**
     * 
     * Get the cleaned name 
     * @param string $name, mod name 
     * @param string $defaultChar, default character
     */
    static function getCleanName($name, $defaultChar = '_') {
        //the first character of modul/Layut name should be [a-zA-Z0-9]|_|-
        $name = capiStrCleanURLCharacters($name);
        //get the first charcte
        $firstChar = substr($name, 0, 1);
        if (! preg_match('/^[a-zA-Z0-9]|_|-$/', $firstChar)) {
            //replace the first character
            $name = $defaultChar. substr($name, 1);
        }
        
        return $name;
    }

    /**
     * 
     * Init the vars of the class.
     * 
     * @param array $modulData [idmod],[name],[input],[output],[forntedpath],[client]
     */
    
    protected function _initModulHandlerWithModulRow($db) {
        if (is_object($db)) {
			global $cfgClient;
			$frontendPath = $cfgClient[$db->f('idclient')]['path']['frontend'];
		
            $this->_modulAlias = $db->f("alias");
            $this->_modulName = $db->f("name");
            $this->_modulPath = $frontendPath . self::$MODUL_DIR_NAME. $this->_modulAlias. "/";
            $this->_path = $frontendPath . self::$MODUL_DIR_NAME;
            $this->_idmod = $db->f("idmod");
            $this->_client = $db->f("idclient");
            $this->_description = $db->f("description");
            $this->_type = $db->f("type");
            $this->_input = "";
            $this->_output = "";
            $this->_echoIt('_initModulHandlerFromDb run idmod '. $this->_idmod);
            $this->_echoIt('frontendpath: '. $frontendPath);
        
        }
    
    }

    /**
     * 
     * Init the vars of the class, make a query to the Db
     * 
     * @param int $idmod the id of the modul
     */
    
    protected function _initModulHandlerFromDb($idmod = NULL) {
        
        if ($idmod== NULL)
            return;
        
        $sql = sprintf(
			"SELECT alias, name, description, type, idclient, idmod FROM %s WHERE idmod=%s", 
			$this->_cfg["tab"]["mod"], 
			Contenido_Security::toInteger($idmod)
		);
        $this->_echoIt("sql :". $sql);
        
        $this->_db->query($sql);
		

        if ($this->_db->next_record()) {
			global $cfgClient;
			$frontendPath = $cfgClient[$this->_db->f('idclient')]['path']['frontend'];
		
            $this->_modulAlias = $this->_db->f('alias');
            $this->_modulName = $this->_db->f("name");
            $this->_modulPath = $frontendPath . self::$MODUL_DIR_NAME. $this->_modulAlias. "/";
            $this->_path = $frontendPath . self::$MODUL_DIR_NAME;
            $this->_idmod = $this->_db->f("idmod");
            $this->_client = $this->_db->f("idclient");
            $this->_description = $this->_db->f("description");
            $this->_type = $this->_db->f("type");
            $this->_input = "";
            $this->_output = "";
            $this->_echoIt('_initModulHandlerFromDb run idmod '. $idmod);
            $this->_echoIt('frontendpath: '. $frontendPath);
        
        }
    }

    /**
     * Get the Modul Path also cms path + module + module name.
     * 
     * @return string
     */
    
    public function getModulPath() {
        return $this->_modulPath;
    }

    /**
     * 
     * Get the template path. If file is set it will
     * return the complete paht + file
     * 
     * @param string $file
     * @return string
     */
    public function getTemplatePath($file = '') {
        
        return $this->_modulPath. $this->_directories['template']. $file;
    
    }

    /**
     * Get the template main file with path
     * @return string
     */
    public function getTemplateMainFile() {
        
        return $this->_modulPath. $this->_directories['template']. $this->_modulAlias. ".html";
    }

    /**
     * Get the css main file with path
     * @return string
     */
    public function getCssMainFile() {
        
        return $this->_modulPath. $this->_directories['css']. $this->_modulAlias. ".css";
    }

    /**
     * 
     * Get the js main file with path 
     * @return string
     */
    public function getJsMainFile() {
        
        return $this->_modulPath. $this->_directories['css']. $this->_modulAlias. ".js";
    }

    /**
     * Get the css path of the modul
     * @return string
     */
    public function getCssPath() {
        
        return $this->_modulPath. $this->_directories['css'];
    }

    /**
     * Get the php path of the modul
     * @return string
     */
    public function getPhpPath() {
        
        return $this->_modulPath. $this->_directories['php'];
    }

    /**
     * 
     * Get the js path of the modul
     * @return string
     */
    public function getJsPath() {
        
        return $this->_modulPath. $this->_directories['js'];
    }

    /**
     * Get the main template file modulename.html
     * @return string
     */
    public function getTemplateFileName() {
        
        return $this->_modulAlias. ".html";
    }

    /**
     * 
     * Get the main css file modulenam.css
     * @return string
     */
    public function getCssFileName() {
        
        return $this->_modulAlias. ".css";
    }

    /**
     * 
     * Return 5 random character 
     * 
     * @return string
     */
    protected function getFiveRandomCharacter() {
        
        $micro1 = microtime();
        $rand1 = rand(0, time());
        $rand2 = rand(0, time());
        return substr(md5($micro1. $rand1. $rand2), 0, 5);
    }

    /**
     * 
     * Check if exist a file 
     * @param string $type js | template | css the directory of the file 
     * @param string $fileName file name
     */
    public function existFile($type, $fileName) {
        
        return file_exists($this->_modulPath. $this->_directories[$type]. $fileName);
    }

    /**
     * 
     * Delete file 
     * @param string $type js |template | css directory of the file
     * @param string $fileName file name  
     */
    public function deleteFile($type, $fileName) {
        
        if (file_exists($this->_modulPath. $this->_directories[$type]. $fileName))
           return unlink($this->_modulPath. $this->_directories[$type]. $fileName);
        else
        	return false;
    }

    /**
     * 
     * Make and save new file 
     * @param string $type css | js | template directory of the file  
     * @param string $fileName file name
     * @param string $content content of the file 
     */
    public function makeNewModuleFile($type, $fileName = NULL, $content = '') {
        
        //make directory if not exist
        $this->makeDirectoryIfNotExist($type);
        
        //if not set use default filename
        if ($fileName== NULL|| $fileName== '') {
            $fileName = $this->_modulAlias;
            
            if ($type== 'template')
                $fileName = $fileName. '.html';
            else
                $fileName = $fileName. ".". $type;
        }
        
        //make and save file contents
        if ($type== 'css'|| $type== 'js'|| $type== 'template') {
            
            if (! file_exists($this->_modulPath. $this->_directories[$type]. $fileName)) {
                
                $content = iconv($this->_encoding, $this->_fileEncoding, $content);
                if (file_put_contents($this->_modulPath. $this->_directories[$type]. $fileName, $content)=== false) {
                    //display error
                    $notification = new Contenido_Notification();
                    $notification->displayNotification('error', i18n("Can't make file: "). $fileName);
                    return false;
                }
            
            } elseif ($content!= '') {
                $content = iconv($this->_encoding, $this->_fileEncoding, $content);
                if (file_put_contents($this->_modulPath. $this->_directories[$type]. $fileName, $content)=== false) {
                    
                    //display error
                    $notification = new Contenido_Notification();
                    $notification->displayNotification('error', i18n("Can't make file: "). $fileName);
                    return false;
                }
            
            }
        }
        return true;
    }

    /**
     * 
     * Rename a file 
     * @param string $type css | js | template directory of the file
     * @param string $oldFileName old name of the file
     * @param string $newFileName the new name of the file 
     * @return boolean by success return true 
     */
    public function renameModulFile($type, $oldFileName, $newFileName) {
        
        if (file_exists($this->_modulPath. $this->_directories[$type]. $newFileName))
            return false;
        
        if (file_exists($this->_modulPath. $this->_directories[$type]. $oldFileName)) {
            if (rename($this->_modulPath. $this->_directories[$type]. $oldFileName, 
            $this->_modulPath. $this->_directories[$type]. $newFileName)== false)
                return false;
            else
                return true;
        } else
            return false;
        
        return true;
    }

    /**
     * 
     * Get the name of the main js file (modulname.js)
     * 
     * @return string the name of the js file
     */
    public function getJsFileName() {
        
        return $this->_modulAlias. ".js";
    }

    /**
     * 
     * Get the content of file, modul js or css or template or php
     * 
     * @param string $directory where in module should we look
     * @param string $fileTyp css or js
     */
    public function getFilesContent($directory, $fileTyp, $fileName = NULL) {
        
        if ($fileName== NULL)
            $fileName = $this->_modulAlias. '.'. $fileTyp;
        
        if (file_exists($this->_modulPath. $this->_directories[$directory]. $fileName)) {
            
            $content = file_get_contents($this->_modulPath. $this->_directories[$directory]. $fileName);
            $content = iconv($this->_fileEncoding, $this->_encoding. "//IGNORE", $content);
            return $content;
        } else
            return false;
    }

    /**
     * 
     * Display a string/int/array, if the flag _debug is set.
     * 
     * @param string or array or int
     */
    protected function _echoIt($output) {
        
        if ($this->_debug) {
            //echo '<pre>';
            file_put_contents("echo_log.txt", $output. "\r\n", FILE_APPEND);
        
     		//echo '</pre>';
        }
    
     //file_put_contents("ausgabe.txt",$output."\n\r" ,FILE_APPEND);
    

    }

    /**
     * 
     * Wirte a error in the file contenido/logs/errorlog.txt
     * 
     * @param string $message
     */
    protected function errorLog($message) {
        
        file_put_contents(dirname(__FILE__). "/../../logs/errorlog.txt", $message. "\r\n", FILE_APPEND);
    
    }

    /**
     * 
     * Get the complete path and file name of the input file
     * @return string 
     */
    public function getInputFile() {
        
        return $this->_modulAlias. $this->_directories['php']. $this->_modulAlias. "_input.php";
    }

    /**
     * 
     * Get the complete path and file name of the output file
     * @return string
     */
    public function getInputOutput() {
        
        return $this->_modulAlias. $this->_directories['php']. $this->_modulAlias. "_output.php";
    }

    /**
     * 
     * Make in all clients the module directory
     */
    public function makeModulMainDirectories() {
		global $cfgClient;
		
		foreach ($cfgClient as $iIdClient => $aClient) {
			if (isset($aClient['path']['frontend'])) {
				$frontendPath = $aClient['path']['frontend'];
				//test if frontendpath exists
				if (is_dir($frontendPath)== false)
					$this->errorLog('Frontendpath dont exists path: '. $frontendPath);
				
				if (! is_dir($frontendPath. self::$MODUL_DIR_NAME)) {
					
					//could not make the modul directory in client 
					if (mkdir($frontendPath. self::$MODUL_DIR_NAME)== false) {
						$this->errorLog("Could not make modul directory in frontendpath :". $frontendPath);
					} else
						chmod($frontendPath. self::$MODUL_DIR_NAME, 0777);
				
				}
			}
		}
    }

    /**
     * 
     * Make main module directory.
     * @return boolean
     * 
     */
    protected function _makeModulDirectory() {
        
        //dont display error ($tpl->generate() on the CONTENIDO login site)
        if ($this->_client== "")
            return - 1;
        
		global $cfgClient;
        $frontendPath = $cfgClient[$this->_client]['path']['frontend'];

        //make 
        if (! is_dir($frontendPath. self::$MODUL_DIR_NAME)) {
            
            if (mkdir($frontendPath. self::$MODUL_DIR_NAME)== false) {
                return false;
            } else
                chmod($frontendPath. self::$MODUL_DIR_NAME, 0777);
        }
        
        return true;
    }

    /**
     * 
     * Get all files from a module directory 
     * @param string $modulDirectory template css or js... 
     * @return array
     */
    public function getAllFilesFromDirectory($modulDirectory) {
        
        $retArray = array();
        $dir = $this->_modulPath. $this->_directories[$modulDirectory];
        
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh))!== false) {
                    
                    //is file a dir or not
                    if ($file!= ".."&& $file!= "."&& ! is_dir($dir. $file. "/")) {
                        
                        $retArray[] = $file;
                    }
                }
            }
        }
        return $retArray;
    }

    /**
     * Set the id of the modul.
     * 
     * @param $idmod int
     */
    public function setIdModul($idmod) {
        
        $this->_idmod = $idmod;
    
    }

    /**
     * Set the new modul name.
     * @var $name string
     */
    
    public function setNewModulName($name) {
        
        $this->_modulAlias = $name;
        $this->_modulPath = $this->_path. $this->_modulAlias. "/";
    }

    /**
     * Warning dont work if more fils exist in the dir
     * then input.php or output.php
     * @return boolean
     * 
     */
    public function eraseModul() {
        
        $ret = NULL;
        
        //if modulName is a string
        if (strlen($this->_modulAlias)> 0)
            $ret = $this->_rec_rmdir($this->_modulPath);
        
        $input = $this->readInput();
        $output = $this->readOutput();
        
        if ($ret== 0)
            return true;
        else
            return false;
    }

    /**
     * Get the id of the modul.
     * 
     * @return int, id of the modul
     */
    public function geIdModul() {
        return $this->_idmod;
    }

    /**
     * Read the input of the file _input.php 
     * 
     * @return string Contents of the Module file (_input.php)
     */
    public function readInput() {
        
        $this->_echoIt("readInput". $this->_modulAlias);
        
        if (file_exists($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_input.php")== FALSE)
            return false;
        
        $content = file_get_contents($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_input.php");
        ;
        $content = iconv($this->_fileEncoding, $this->_encoding. "//IGNORE", $content);
        return $content;
    }

    /**
     * Read the output of the file _output.php
     * 
     * @return string Contents of the Module file( _output.php)
     */
    public function readOutput() {
        
        $this->_echoIt("readOutput". $this->_modulAlias);
        
        if (file_exists($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_output.php")== FALSE)
            return false;
        
        $content = file_get_contents($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_output.php");
        $content = iconv($this->_fileEncoding, $this->_encoding. "//IGNORE", $content);
        return $content;
    }

    /**
     * 
     * Make a directory template/css/image/js/php if not exist
     * 
     * @param string $type 
     */
    protected function makeDirectoryIfNotExist($type) {
        
        if (array_key_exists($type, $this->_directories))
            if (! is_dir($this->_modulPath. $this->_directories[$type])) {
                
                if (mkdir($this->_modulPath. $this->_directories[$type])== false) {
                    return false;
                } else
                    chmod($this->_modulPath. $this->_directories[$type], 0777);
            }
    
    }

    /**
     * 
     * Save a string into the file (_output.php).
     * 
     * @param string 
     * @return bool if the action (save contents into the file _output.php is success) return true else false
     */
    public function saveOutput($output = NULL) {
        
        $this->_echoIt("saveOutput". $this->_modulAlias. "output: ");
        
        $this->makeDirectoryIfNotExist('php');
        if ($output== NULL)
            $output = $this->_output;
        
        $output = iconv($this->_encoding, $this->_fileEncoding, $output);
        if (file_put_contents($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_output.php", $output, 
        LOCK_EX)=== FALSE)
            return false; //return false if file_put_contents dont work
        else {
            chmod($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_output.php", 0666);
            return true; //return true if file_put_contents working
        }
    
    }

    /**
     * 
     * Save a string into the file (_input.php)
     * 
     * @param string 
     * @return bool if the action (save contents into the file _input.php is success) return true else false
     */
    public function saveInput($input = NULL) {
        
        $this->_echoIt("saveInput". $this->_modulAlias. " input: ");
        
        $this->makeDirectoryIfNotExist('php');
        if ($input== NULL)
            $input = $this->_input;
        $input = iconv($this->_encoding, $this->_fileEncoding, $input);
        if (file_put_contents($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_input.php", $input, 
        LOCK_EX)=== FALSE) {
            return false;
        } else {
            chmod($this->_modulPath. $this->_directories['php']. $this->_modulAlias. "_input.php", 0666);
            return true;
        }
    }

    /**
     * 
     * This method save a xml file with modul information.
     * If the params not set, get the value from this
     * @param string $modulName name of the modul
     * @param string $description description of the modul
     * @param string $type type of the modul
     * @return true if success else false
     */
    public function saveInfoXML($modulName = NULL, $description = NULL, $type = NULL, $alias = NULL) {
        
        $tree = new XmlTree('1.0', 'ISO-8859-1');
        $root = & $tree->addRoot('module');
        
        if ($modulName== NULL)
            $modulName = $this->_modulName;
        
        if ($description== NULL)
            $description = $this->_description;
        
        if ($type== NULL)
            $type = $this->_type;
        
        if ($alias== NULL)
            $alias = $this->_modulAlias;
        
        $root->appendChild("name", htmlspecialchars($modulName));
        $root->appendChild("description", htmlspecialchars($description));
        $root->appendChild("type", htmlspecialchars($type));
        $root->appendChild("alias", htmlspecialchars($alias));
        
        if (file_put_contents($this->_modulPath. self::$NAME_OF_INFO_XML, $tree->dump(true))=== FALSE)
            return false;
        else
            return true;
    
    }

    /**
     * Make a new module into the modul dir. The modul name will be [ModulName] example
     * Contact_Form or GoogleMaps2.
     * 
     * @return bool if modul exist or mkdir and saveInput and saveOutput success return true.  
     * Else if the mkdir or saveInput or saveOutput not success return false.   
     */
    public function makeNewModul($input = "", $output = "") {
        
        if ($input!= "")
            $this->_input = $input;
        
        if ($output!= "")
            $this->_output = $output;
        
        if ($this->existModul()== false) {
            if (mkdir($this->_modulPath)== FALSE) {
                return false;
            } else {
                chmod($this->_modulPath, 0777);
            }
            
            //make others directorys
            foreach ($this->_directories as $directory) {
                
                if (! is_dir($this->_modulPath. $directory)) {
                    
                    if (mkdir($this->_modulPath. $directory)== false) {
                        return false;
                    } else
                        chmod($this->_modulPath. $directory, 0777);
                }
            
            }
            
            //could not save the info xml 
            if ($this->saveInfoXML()== false)
                return false;
            
     //Save empty strings into the modul files, if someone trying to read contents bevore save into the files
            $retInput = $this->saveInput();
            $retOutput = $this->saveOutput();
            
            if ($retInput== false|| $retOutput== false)
                return false;
            
            return true;
        } else
            return true;
    }

    /**
     * 
     * Rename a modul and the input and output files.
     * 
     * @param string $old  old name of the modul
     * @param string $new new name of the modul
     * 
     * @return booelan true if success 
     */
    public function renameModul($old, $new) {
        
        $this->_echoIt("renameModul ". $this->_modulAlias. " old: $old  new: $new");
        
        //try to rename the dir
        if (rename($this->_path. $old, $this->_path. $new)== FALSE)
            return false;
        else {
            
            $retInput = true;
            $retOutput = true;
            
            //if file input exist rename it
            if (file_exists($this->_path. $new. "/". $this->_directories['php']. $old. "_input.php"))
                $retInput = rename($this->_path. $new. "/". $this->_directories['php']. $old. "_input.php", 
                $this->_path. $new. "/". $this->_directories['php']. $new. "_input.php");
            
     		//if file output exist rename it  
            if (file_exists($this->_path. $new. "/". $this->_directories['php']. $old. "_output.php"))
                $retOutput = rename($this->_path. $new. "/". $this->_directories['php']. $old. "_output.php", 
                $this->_path. $new. "/". $this->_directories['php']. $new. "_output.php");
            
     		//rename the  css file 
            if (file_exists($this->_path. $new. "/". $this->_directories['css']. $old. ".css"))
                rename($this->_path. $new. "/". $this->_directories['css']. $old. ".css", 
                $this->_path. $new. "/". $this->_directories['css']. $new. ".css");
            
     		//rename the javascript file
            if (file_exists($this->_path. $new. "/". $this->_directories['js']. $old. ".js"))
                rename($this->_path. $new. "/". $this->_directories['js']. $old. ".js", 
                $this->_path. $new. "/". $this->_directories['js']. $new. ".js");
            
     		//rename the template file 
            if (file_exists($this->_path. $new. "/". $this->_directories['template']. $old. ".html"))
                rename($this->_path. $new. "/". $this->_directories['template']. $old. ".html", 
                $this->_path. $new. "/". $this->_directories['template']. $new. ".html");
            
            if ($retInput== true&& $retOutput== true)
                return true;
            else
                return false;
        }
    
    }

    /**
     * This method get the db-table row of the modul.
     * 
     * @param string $name  name of the modul
     * @return array the modul row as array
     */
    public function getModulByName($name) {
        
        $myDb = new DB_Contenido();
        $sql = sprintf("SELECT * FROM %s WHERE name ='%s' AND idclient=%s ", $this->_cfg["tab"]["mod"], $name, 
        $this->_client);
        
        $myDb->query($sql);
        if ($myDb->next_record()!= false)
            return $myDb->copyResultToArray();
        else
            return null;
    
    }

    /**
     * 
     * @return int, count how much is the name of modul in db-table 
     */
    public function countModulNameInDb() {
        $myDb = new DB_Contenido();
        $sql = sprintf("SELECT count(*) as count FROM %s WHERE name ='%s' AND idclient =%s ", $this->_cfg["tab"]["mod"], 
        $this->_modulAlias, $this->_client);
        
        $myDb->query($sql);
        $myDb->next_record();
        
        return $myDb->f("count");
    }

    /**
     * Show if the Modul with the modul name exist in modul dir.
     * 
     * return bool if the modul exist return true, else false
     */
    public function existModul() {
        
        if (is_dir($this->_modulPath)) {
            
            $this->_echoIt("existModul". $this->_modulAlias. " return: true");
            return true;
        } else {
            $this->_echoIt("existModul". $this->_modulAlias. " return: false");
            return false;
        }
    }

    /**
     * 
     * This method erase a directory recrusive. 
     * 
     * @param string $path
     * @return 0 all right, -1 paht is not a direcrotry, -2 erro at erase, -3 unknown type of file in directory
     */
    private function _rec_rmdir($path) {
        // schau' nach, ob das ueberhaupt ein Verzeichnis ist
        if (! is_dir($path)) {
            return - 1;
        }
        // oeffne das Verzeichnis
        $dir = @opendir($path);
        
        // Fehler?
        if (! $dir) {
            return - 2;
        }
        
        // gehe durch das Verzeichnis
        while (($entry = @readdir($dir))!== false) {
            // wenn der Eintrag das aktuelle Verzeichnis oder das Elternverzeichnis
            // ist, ignoriere es
            if ($entry== '.'|| $entry== '..')
                continue;
            
     // wenn der Eintrag ein Verzeichnis ist, dann 
            if (is_dir($path. '/'. $entry)) {
                // rufe mich selbst auf
                $res = $this->_rec_rmdir($path. '/'. $entry);
                // wenn ein Fehler aufgetreten ist
                if ($res== - 1) { // dies duerfte gar nicht passieren
                    @closedir($dir); // Verzeichnis schliessen
                    return - 2; // normalen Fehler melden
                } else if ($res== - 2) { // Fehler?
                    @closedir($dir); // Verzeichnis schliessen
                    return - 2; // Fehler weitergeben
                } else if ($res== - 3) { // nicht unterstuetzer Dateityp?
                    @closedir($dir); // Verzeichnis schliessen
                    return - 3; // Fehler weitergeben
                } else if ($res!= 0) { // das duerfe auch nicht passieren...
                    @closedir($dir); // Verzeichnis schliessen
                    return - 2; // Fehler zurueck
                }
            } else if (is_file($path. '/'. $entry)|| is_link($path. '/'. $entry)) {
                // ansonsten loesche diese Datei / diesen Link
                $res = @unlink($path. '/'. $entry);
                // Fehler?
                if (! $res) {
                    @closedir($dir); // Verzeichnis schliessen
                    return - 2; // melde ihn
                }
            } else {
                // ein nicht unterstuetzer Dateityp
                @closedir($dir); // Verzeichnis schliessen
                return - 3; // tut mir schrecklich leid...
            }
        }
        
        // schliesse nun das Verzeichnis
        @closedir($dir);
        
        // versuche nun, das Verzeichnis zu loeschen
        $res = @rmdir($path);
        
        // gab's einen Fehler?
        if (! $res) {
            return - 2; // melde ihn
        }
        
        // alles ok
        return 0;
    }

}

?>