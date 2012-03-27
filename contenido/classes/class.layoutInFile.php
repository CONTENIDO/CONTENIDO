<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * CONTENIDO Layout in filesystem function
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release >= 4.9
 * 
 * }}
 * 
 */
    
class LayoutInFile {
	/**
	 * 
	 * The html/code of the layout
	 * 
	 * @var string
	 */
	protected $_layoutCode = "";
	
	protected $_db = null;
	
	/**
	 * 
	 * Layout name 
	 * @var string
	 */
	protected $_layoutName = "";
	
	/**
	 * 
	 * The contenido cfg
	 * 
	 * @var array
	 */
	protected $_cfg = array();
	
	/**
	 * 
	 * Encoding of the page 
	 * 
	 * @var string
	 */
	protected $_encoding;
	
	
	/**
	 * 
	 * The id of the layout
	 * @var int
	 */
	protected $_layoutId = "";
	
	
	/**
	 * 
	 * Hole path of the directory
	 * [frontendpath].layouts/.layoutName/
	 * @var string
	 */
	protected $_layoutPath = "";
	
	/**
	 * 
	 * Main path of layouts.
	 * [frontendpath].layouts
	 * @var string
	 */
	protected $_layoutMainPath = "";
	
	/**
	 * 
	 * File name of the layout ([layoutname].html
	 * @var string
	 */
	protected $_fileName = "";

	protected $_frontendPath = "";
	
	/**
	 * 
	 * Construct of the class 
	 * @param int $layoutId
	 * @param array $cfg
	 * @param string $encoding
	 */
	public function __construct($layoutId, $layoutCode, $cfg, $lang, $db = null ) {
		if ($db === null) {
			$db = new DB_Contenido();
		}
		
		$this->_db = $db;
		$this->init($layoutId, $layoutCode, $cfg, $lang);
	}
	
	/**
	 * 
	 * Look in layout directory if layout [$layoutAlias] directory exists 
	 * 
	 * @param string $layoutAlias
	 * @return boolen if file exist true 
	 */
	static function existLayout($layoutAlias, $cfgClient, $client) {
		$file = $cfgClient[$client]['path']['frontend'] . "layouts/" . $layoutAlias.'/';
		return file_exists($file);
	}

	/**
	 * 
	 * Init all vars for the class
	 * 
	 * @param int $layoutId
	 * @param array $cfg
	 * @param string $encoding
	 */
	public function init($layoutId, $layoutCode, $cfg, $lang) {
		$this->_layoutId = $layoutId;
		$this->_layoutCode = $layoutCode;
		$this->_cfg = $cfg;
		
		#set encoding
		$this->_setEncoding($lang);
		
		#get name of layout and frontendpath
		$sql = sprintf("SELECT alias FROM %s WHERE idlay = %s", $this->_cfg["tab"]["lay"], $layoutId);
		$db = clone $this->_db;

		$db->query($sql);
		$db->next_record();

		global $cfgClient, $client;
			
        $frontendPath = $cfgClient[$client]['path']['frontend'];

		$this->_layoutName = $db->f('alias');
		$this->_frontendPath = $frontendPath;
		$this->_layoutMainPath = $frontendPath . "layouts/";
		$this->_layoutPath = $this->_layoutMainPath . $this->_layoutName."/";
		$this->_fileName = $this->_layoutName . ".html";
		
		#make directoryies for layout
		$this->_makeDirectories();
	}

	/**
	 * init
	 * Get the layout name
	 * @return string layoutname
	 */
	public function getLayoutName() {
		return $this->_layoutName;
	}
	
	/**
	 * 
	 * Init class vars with values, only use for setup or upgrade
	 * 
	 * @param DB_Contenido $dbObject
	 */
	public function initWithDbObject($dbObject ) {
		global $cfgClient, $client;
			
        $frontendPath = $cfgClient[$client]['path']['frontend'];
		
		$this->_layoutCode = $dbObject->f("code");
		$this->_layoutName = $dbObject->f('alias');
		$this->_frontendPath = $frontendPath;
		$this->_layoutMainPath = $this->_frontendPath . "layouts/";
		$this->_layoutPath = $this->_layoutMainPath . $this->_layoutName . "/";
		$this->_fileName = $this->_layoutName.".html";
		
		#make directoryies for layout
		$this->_makeDirectories();
	}
	
	/**
	 * Make all directories for layout. Main directory and Layout directory
	 * @return boolean true if successfully
	 */
	private function _makeDirectories() {
		if ($this->_makeDirectory($this->_layoutMainPath)) {
			if ($this->_makeDirectory($this->_layoutPath)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Make directory
	 * 
	 * @param string $directory
	 * @return boolean true if succssesfully
	 */
	private function _makeDirectory($directory) {
		if (is_dir($directory)) {
			return true;
		}
		
		if (mkdir($directory)) {
		    chmod($directory, 0777);
		    return true;
		}
		
		return false;
	}

	/**
	 *
	 * Save encoding from client.
	 * @param array $cfg
	 * @param int $lang
	 */
	private  function _setEncoding($lang) {

		$db = clone $this->_db;
		$sql = "SELECT idlang, encoding FROM ".$this->_cfg["tab"]["lang"];
		$db->query($sql);
		$aLanguageEncodings = array();
			
		while ($db->next_record())
		{
			$aLanguageEncodings[$db->f("idlang")] = $db->f("encoding");
		}
		 
		if (array_key_exists($lang, $aLanguageEncodings))
		{
			if (!in_array($aLanguageEncodings[$lang], $this->_cfg['AvailableCharsets']))
			{
				$this->_encoding = "ISO-8859-1";

			} else {
				$this->_encoding = $aLanguageEncodings[$lang] ;
			}
		} else {
			$this->_encoding = "ISO-8859-1";
		}

	}
	
	/**
	 * Can write/create a file
	 *
	 * @param string $fileName file name
	 * @param string $directory directory where is the file
	 * @return boolean, success true else false
	 */
	public function isWritable($fileName, $directory) {
		 
		if(file_exists($fileName)) {
			if(!is_writable($fileName)) {
				return false;
			}
		}else {
			if(!is_writable($directory)) {
				return false;
			}
		}
		return true;
	}
	
    
	/**
	 * Save Layout 
	 * 
	 * @param string $layoutCode
	 * 
	 * @return boolean true 
	 */
	public function saveLayout($layoutCode = "") {
		
		$fileName = $this->_layoutPath . $this->_fileName;
		
		if(!$this->isWritable($fileName, $this->_layoutPath)) {
			return false;
		}
		
		
			
		if($layoutCode == "")
			$layoutCode = $this->_layoutCode;
		//exist layout path
		if( is_dir($this->_layoutPath)) {
			//convert
			$fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
			$layoutCode = iconv($this->_encoding, $fileEncoding, $layoutCode);
		
			if ( file_put_contents($this->_layoutPath . $this->_fileName, $layoutCode) === FALSE ) {
				return false;
			}
			else { 	
				chmod($this->_layoutPath . $this->_fileName, 0777);
			    return true;
			}
		} else 
			return false;		
	}
	
	
	
	
	/**
	 * 
	 * 
	 * Save the layout only if layout dont exist in filesystem!
	 * Use it for upgrade! 
	 * 
	 * @param string $layoutCode
	 */
	public function saveLayoutByUpgrade($layoutCode = "") {
		
		// if file exist dont overwirte it 
		if(file_exists($this->_layoutPath.$this->_fileName)) {
			//
			return true ;
		}
		
		if($layoutCode == "")
			$layoutCode = $this->_layoutCode;
		#exist layout path
		if( is_dir($this->_layoutPath)) {
			#convert
			$fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
			$layoutCode = iconv($this->_encoding, $fileEncoding, $layoutCode );
			if ( file_put_contents($this->_layoutPath . $this->_fileName, $layoutCode) === FALSE )
				return false;
			else 	
				return true;
		} else 
			return false;	
			
	}
	
	
	/**
     * 
     * Return 5 random character 
     * 
     * @return string
     */
    static  function getFiveRandomCharacter() {
        
        $micro1 = microtime();
        $rand1 = rand(0,time());
        $rand2 = rand(0,time());
        return substr(md5($micro1.$rand1.$rand2),0,5);
    }
    
	
	  /**
     * 
     * This method erase a directory recrusive. 
     * 
     * @param string $path
     * @return 0 all right, -1 paht is not a direcrotry, -2 erro at erase, -3 unknown type of file in directory
     */
    private function _rec_rmdir ($path) {
        // schau' nach, ob das ueberhaupt ein Verzeichnis ist
        if (!is_dir ($path)) {
        return -1;
        }
        // oeffne das Verzeichnis
        $dir = @opendir ($path);
    
        // Fehler?
        if (!$dir) {
            return -2;
        }
    
        // gehe durch das Verzeichnis
        while (($entry = @readdir($dir)) !== false) {
            // wenn der Eintrag das aktuelle Verzeichnis oder das Elternverzeichnis
            // ist, ignoriere es
            if ($entry == '.' || $entry == '..') continue;
            // wenn der Eintrag ein Verzeichnis ist, dann 
            if (is_dir ($path.'/'.$entry)) {
                // rufe mich selbst auf
                $res = $this->_rec_rmdir ($path.'/'.$entry);
                // wenn ein Fehler aufgetreten ist
                if ($res == -1) { // dies duerfte gar nicht passieren
                    @closedir ($dir); // Verzeichnis schliessen
                    return -2; // normalen Fehler melden
                } else if ($res == -2) { // Fehler?
                    @closedir ($dir); // Verzeichnis schliessen
                    return -2; // Fehler weitergeben
                } else if ($res == -3) { // nicht unterstuetzer Dateityp?
                    @closedir ($dir); // Verzeichnis schliessen
                    return -3; // Fehler weitergeben
                } else if ($res != 0) { // das duerfe auch nicht passieren...
                    @closedir ($dir); // Verzeichnis schliessen
                    return -2; // Fehler zurueck
                }
            } else if (is_file ($path.'/'.$entry) || is_link ($path.'/'.$entry)) {
                // ansonsten loesche diese Datei / diesen Link
                $res = @unlink ($path.'/'.$entry);
                // Fehler?
                if (!$res) {
                    @closedir ($dir); // Verzeichnis schliessen
                    return -2; // melde ihn
                }
            } else {
                // ein nicht unterstuetzer Dateityp
                @closedir ($dir); // Verzeichnis schliessen
                return -3; // tut mir schrecklich leid...
            }
        }
    
        // schliesse nun das Verzeichnis
        @closedir ($dir);
    
        // versuche nun, das Verzeichnis zu loeschen
        $res = @rmdir ($path);
    
        // gab's einen Fehler?
        if (!$res) {
            return -2; // melde ihn
        }
    
        // alles ok
        return 0;
    }
    
    
    /**
     * 
     *Erase complete a layout+
     *@return boolean true if succsses
     */
	public  function eraseLayout() {
		
		if( $this->_rec_rmdir($this->_layoutPath) != 0)
			return false;
		
		return true;
	}
	
	
	/**
	 * 
	 * Rename the Layout directory and layout file
	 * @param string $old
	 * @param string $new
	 */
	public function rename($old, $new ) {
		
		#try to rename the dir
		$newPath = $this->_layoutMainPath . $new . "/";
		
		$newFileName = $new . ".html";
		
        if(rename($this->_layoutPath, $newPath ) == FALSE )
            return false;
        else {
            #if file input exist rename it
            if(file_exists($newPath . $this->_fileName))
               if(rename($newPath . $this->_fileName, $newPath . $newFileName)) {
               	
            	$this->_layoutName = $new;
				$this->_layoutPath = $this->_frontendPath . "layouts/" . $this->_layoutName . "/";
				$this->_fileName = $this->_layoutName.".html";
		
               	return true;
               }
               else 
               	return false; 
        }
		
        return false;
	}
	/**
	 * 
	 * Get the contents of the file
	 * 
	 * @return content or false
	 */
	public function getLayoutCode() {
		
		// cant read it dont exist file
		if (! is_readable ( $this->_layoutPath . $this->_fileName )) {
			return false;
		}
		
		if (($content = file_get_contents ( $this->_layoutPath . $this->_fileName )) === FALSE)
			return false;
		else {
			// convert
			$fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
			$content = iconv($fileEncoding, $this->_encoding . "//IGNORE", $content );
			return $content;
		}
	
	}
	
	
	
	/**
	 * 
	 * Save all layouts in file system 
	 * 
	 */
	public function setup() {
		
		
		
	}
	
	
	/**
	 * 
	 * Save all layout in file system.
	 * Use it for upgrade.
	 */
	public function upgrade() {
		#get name of layout and frontendpath
		$sql = sprintf("SELECT alias, idlay, code FROM %s", $this->_cfg["tab"]["lay"]);
		$db = clone $this->_db;
		$db->query($sql);
		$isError = false;

		while($db->next_record()) {
			// init class var for save
			$this->initWithDbObject($db);
			if( $this->saveLayoutByUpgrade($db->f("code")) == false) {
				cWarning(__FILE__, __LINE__, "Can not save layout name: " . $this->_layoutName);
				$isError = true;
			}
		}
		
		#all layouts are saved
		if(!$isError) {
			 #remove the code field from _lay
             $sql = sprintf("ALTER TABLE %s DROP code", $this->_cfg["tab"]["lay"]);
             $db->query($sql);
		}
			
	}
}

?>