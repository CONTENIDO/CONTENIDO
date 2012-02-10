<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This class collect all the contenido variables ($client, $encoding, $cfg, $cfgClient ...).
 * With Contenido_Vars::getVar('<contenido_var>', $client) we set variables. With getVar('<contenido_var>') we
 * get the value.
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
 *   created 2011-10-22
 *
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}  

/**
 * 
 * This class collect all the contenido variables ($client, $encoding, $cfg, $cfgClient ...).
 * With Contenido_Vars::getVar('<contenido_var>', $client) we set variables. With getVar('<contenido_var>') we
 * get the value.
 * 
 * 
 * @author rusmir.jusufovic
 *
 */
	class Contenido_Vars {
		/**
		 * 
		 * Array with CONTENIDO vars
		 * @var array
		 */
		static $contenidoVar = array();
		
		public function __construct() {
			
			
			
		}
		
		
		
		/**
		 * 
		 * Set a CONTENIDO var with contents/value
		 * @param string $name
		 * @param string $value
		 */
		public static  function setVar($name, $value) {
			
			self::$contenidoVar[$name] = $value;
			
		}
		
		/**
		 * 
		 * Name of the CONTENIDO var db, encoding, lang, cfg, cfgClient ...
		 * @param string $name
		 * @return content of found CONTENIDO var or null
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
		
		/**
		 * 
		 * Show all $contenido variables 
		 */
		public static function debugg() {	
			echo "<pre>";
			print_r(self::$contenidoVar);
			echo "</pre>";
		}
		
	}
?>