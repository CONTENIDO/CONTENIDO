<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This class save the translations from a modul in a file
 * and get it from file. 
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
 * mofified, Rusmir Jusufovic, add reset for the class.property.php
 *
 * }}
 *
 */

if(file_exists(dirname(__FILE__)."/class.contenido.module.handler.php"))
    include_once(dirname(__FILE__)."/class.contenido.module.handler.php");
    

if(file_exists(dirname(__FILE__)."/../class.genericdb.php"))
    include_once(dirname(__FILE__)."/../class.genericdb.php");
    
if(file_exists(dirname(__FILE__)."/../class.lang.php"))
    include_once(dirname(__FILE__)."/../class.lang.php");
    
    
/**
 * 
 * This class save the translations from a modul in a file
 * and get it from file. 
 * 
 * @author rusmir.jusufovic
 *
 */

class Contenido_Translate_From_File extends Contenido_Module_Handler{
    
    /**
     * 
     * Path to the modul directory
     * @var string
     */ 
    private $_modulePath;
   
   
    /**
     * 
     * Name of the translations file
     * @var string
     */
    static $fileName = "";
    /**
     * Translation array.
     * @var array
     */
    static $langArray  = array();
    
    /**
     * 
     * The id of the modul
     * @var int
     */
    static $savedIdMod = NULL; 
   
   
    static $originalTranslationDivider = '=';
    
    
    /**
     * 
     * @param array $cfg
     * @param int $idclient
     * @param int $idmodul
     * @param int $idlang
     * @param boolean $static if true it will load once the translation from file
     */
    public function __construct($idmodul = null, $static = false  ) {
       parent::__construct($idmodul);
       
       $this->_debug = true;
      
	 	if($idmodul != null)
      		$this->_modulePath = $this->getModulePath();
      
        //dont open the translations file for each mi18n call 
      	if($static == true) {
          if( Contenido_Translate_From_File::$savedIdMod != $idmodul) {
          	  //set filename lang_[language]_[Country].txt
              $language = $this->_getValueFromProperties("language","code");
              $country = $this->_getValueFromProperties("country", "code");
              self::$fileName = "lang_".$language."_".strtoupper($country).".txt";
              
              Contenido_Translate_From_File::$langArray = $this->getTranslationArray();
              Contenido_Translate_From_File::$savedIdMod = $idmodul;
          }   
      	}
      	else {
              Contenido_Translate_From_File::$savedIdMod = -1;
              
             //set filename lang_[language]_[Country].txt
              $language = $this->_getValueFromProperties("language","code");
              $country = $this->_getValueFromProperties("country", "code");
              self::$fileName = "lang_".$language."_".strtoupper($country).".txt";
              self::$fileName = "lang_".$language."_".strtoupper($country).".txt";
      	} 
    }
    
    
  
    
    /**
     * 
     * Get the value of a item from properties db.
     * @param string $type
     * @param string $name
     * @return string value 
     */
    private function  _getValueFromProperties($type , $name) {
    	cApiPropertyCollection::reset();
		$propColl = new cApiPropertyCollection();
        $propColl->changeClient($this->_client);
        return $propColl->getValue('idlang', $this->_idlang, $type, $name, '');
    }
    
    /**
     * 
     * Get the lang array.
     * @return array
     */
    public function getLangArray() {
        
        return Contenido_Translate_From_File::$langArray;
    }
    
    /**
     * Save all translations from db in Filesystem.
     * Warning let run once, twice will be erase the translation witch are
     * there.
     * 
     */
    public function saveTranslationsFromDbToFile() {
        
        $db = new DB_Contenido();
        $sql = sprintf('SELECT clang.idlang as idlang,client.idclient as idclient,modul.idmod as idmod FROM %s as clang , %s as modul, %s as client WHERE clang.idclient=client.idclient ', $this->_cfg['tab']['clients_lang'],$this->_cfg['tab']['mod'],$this->_cfg['tab']['clients']);
        
        $db->query($sql);
        
        while($db->next_record()) {  
            $contenidoTranslationsFromFile = new Contenido_Translate_From_File($db->f('idmod'));
            $contenidoTranslationsFromFile->saveTranslations();
            
        }   
    }
    
    
    /**
     * 
     * @todo noch nicht fertig 
     */
    
    public function saveAllTranslations() {
    	
    	$db = new DB_Contenido();
    	$sql = "SELECT module.idmod,
    					translation.idlang, 
    					translation.original,
    					translation.translation 
    			FROM 	con_mod_translations AS translation , 
    					con_mod AS module 
    			WHERE  	translation.idmod = module.idmod 
    			ORDER BY module.idmod, translation.idlang";
    	
    	$db->query($sql);
    	
    	$transArray = array();
    	$saveModId= -1;
    	$saveLangId = -1;
    	while($db->next_record()) {
    		
    		$transArray[Contenido_Security::unfilter($db->f('original'))] = Contenido_Security::unfilter($db->f('translation'));
			
    		if($saveLangId != $db->f('idlang') || $saveModId != $db->f('idmod')) {
    			
    			if($saveLangId != -1 && $saveModId != -1) {
					
    				//save the translation
    				
    				
    				//reset translations array
    				$transArray = array();
    				
    			}	
    		}
    		
    		$saveLangId = $db->f('idlang');
    		$saveModId = $db->f('idmod');	
    	}
    	
    	
    }
    
    /**
     *Save the hole translations for a idmod and lang.
     *For the upgrade/setup. 
     **/
    public function saveTranslations() {
     
		$dbLanguage = new DB_Contenido();
		$sqlLanguage = sprintf("SELECT * FROM %s", $this->_cfg['tab']['lang']);
		$dbLanguage->query($sqlLanguage);

		while ($dbLanguage->next_record()) {
			$db = new DB_Contenido();
			$sql = sprintf('SELECT * FROM %s WHERE idlang=%s AND idmod=%s' , $this->_cfg['tab']['mod_translations'] , $dbLanguage->f('idlang') , $this->_idmod);

			$db->query($sql);
			
		   $this->_idlang = $dbLanguage->f('idlang');
		   //set filename lang_[language]_[Country].txt
		   $language = $this->_getValueFromProperties("language","code");
		   $country = $this->_getValueFromProperties("country", "code");
		   self::$fileName = "lang_".$language."_".strtoupper($country).".txt";
			
			$translations = array();
			while ($db->next_record()) { 
				$translations[Contenido_Security::unfilter($db->f('original'))] = Contenido_Security::unfilter($db->f('translation'));
			}
			
			if (count($translations) != 0) {
				if ($this->saveTranslationArray($translations) == false) {
					cWarning(__FILE__, __LINE__, 'Could not save translate idmod='.$this->_idmod.' !');
				}
			}
		}
    }
    
    
    /**
     * 
     * This method serialize a array.
     * $key.[Divider].$value."\r\n"
     * 
     * @param array $wordListArray
     * @return string
     */
    private function _serializeArray($wordListArray ) {
        
        $retString ='';
        foreach( $wordListArray as $key => $value) {
        	$value = iconv($this->_encoding,$this->_fileEncoding, $value);
        	$key = iconv($this->_encoding,$this->_fileEncoding, $key);
            //Originall String [Divider] Translation String
            $retString .= $key.Contenido_Translate_From_File::$originalTranslationDivider.$value."\r\n";
        }
        
        return $retString;
    }
    
   /**
    * 
    * This method unserialize a string.
    * The contents of file looks like original String [Divider] Translation String.
    * If divider is = 
    * Example: Hello World=Hallo Welt 
    * 
    * @param string $string the contents of the file
    * @return array
    */
    private function _unserializeArray($string) {

        $retArray= array();
       
        $words = preg_split('((\r\n)|(\r)|(\n))',$string);
       
        foreach($words as $key => $value) {
            $oriTrans = explode(Contenido_Translate_From_File::$originalTranslationDivider,$value);

            if(!empty($oriTrans[0])) {
                if(isset($oriTrans[1])) {
                    $retArray[iconv($this->_fileEncoding, $this->_encoding,$oriTrans[0])] = iconv($this->_fileEncoding, $this->_encoding,$oriTrans[1]);
                }else
                    $retArray[iconv($this->_fileEncoding, $this->_encoding,$oriTrans[0])] = '';
            }

            
        }
            return $retArray;    
            
    } 
    /**
     * 
     * Save the contents of the wordListArray in file.
     * @param array $wordListArray
     * @return boolean true if success else false
     */
    public function saveTranslationArray($wordListArray) {
    	
       $this->createModuleDirectory('lang');
       if( file_put_contents($this->_modulePath.$this->_directories['lang'].self::$fileName,$this->_serializeArray($wordListArray))=== false)
           return false;
        else
            return true;    
    }
    
    
    /**
     * 
     * Get the translations array.
     * @return array
     */
    public function getTranslationArray() {
        if(file_exists($this->_modulePath.$this->_directories['lang'].self::$fileName)) {
            $array = $this->_unserializeArray(file_get_contents($this->_modulePath.$this->_directories['lang'].self::$fileName));
            return $array;
        }
        else 
            return  array();    
    }
    
    
}


?>