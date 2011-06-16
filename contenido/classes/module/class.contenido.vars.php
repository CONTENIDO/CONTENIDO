<?php

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}  

/**
 * 
 * A structure with method for contenido vars.
 * 
 * @author rusmir.jusufovic
 *
 */
	class Contenido_Vars {
		/**
		 * 
		 * Array with contenido vars
		 * @var array
		 */
		static $contenidoVar = array();
		
		public function __construct() {
			
			
			
		}
		
		
		
		/**
		 * 
		 * Set a contenido var with contents/value
		 * @param string $name
		 * @param string $value
		 */
		public static  function setVar($name, $value) {
			
			self::$contenidoVar[$name] = $value;
			
		}
		
		/**
		 * 
		 * Name of the contenido var db, encoding, lang, cfg, cfgClient ...
		 * @param string $name
		 * @return content of fond contenido var or null
		 */
		public static function getVar($name) {
			
			if(array_key_exists($name,self::$contenidoVar)) {
				
				return self::$contenidoVar[$name];
			} else {
				
				return null;
			}
			
		}
		
		
		/**
		 * 
		 * Save encoding from client.
		 * @param DB_Contenido $db
		 * @param array $cfg
		 * @param int $lang
		 */
		public static function setEncoding($db, $cfg , $lang) {
			
			$sql = "SELECT idlang, encoding FROM ".$cfg["tab"]["lang"];
	        $db->query($sql);
	        $aLanguageEncodings = array();
	        
	       
	        while ($db->next_record())
	        {
	            $aLanguageEncodings[$db->f("idlang")] = $db->f("encoding");
	        }
	        
	        if (array_key_exists($lang, $aLanguageEncodings))
	        {
	            if (!in_array($aLanguageEncodings[$lang], $cfg['AvailableCharsets']))
	            {
	            	self::$contenidoVar['encoding'] = "ISO-8859-1";
	                     
	            } else {
	                self::$contenidoVar['encoding'] = $aLanguageEncodings[$lang] ;       
	            }
	        } else {
	           self::$contenidoVar['encoding'] = "ISO-8859-1";
	        }
        
		}
		
		public static function debugg() {	
			echo "<pre>";
			print_r(self::$contenidoVar);
			echo "</pre>";
		}
		
	}
?>