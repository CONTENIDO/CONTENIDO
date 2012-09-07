<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Converts characters between their normalized and diacritic representation.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.chartable.php 601 2008-07-11 13:08:36Z dominik.ziegler $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * cCharacterConverter
 * 
 * A diacritic mark or diacritic is a mark added to a letter to alter a
 * word's pronunciation or to distungish between similar words. However,
 * users of foreign languages are unable to type diacritics (either because
 * the keyboard mapping doesn't support them, or they are looking to similar
 * to other characters). Examples for conversions:
 * 
 * German diacritic char ü maps to u and ue.
 * 
 * Developers can use the diacritic search implemented in the GenericDB to
 * automatically handle diacritic search conversion.
 */
class cCharacterConverter
{
	var $_oDB;
	var $_aAliasCache;
	var $_aCharCache;
	
	function cCharacterConverter ()
	{
		$this->_oDB = new DB_Contenido;
		$this->_aAliasCache = array();
		$this->_aCharCache = array();
	}
	
	function fetchDiacriticCharactersForNormalizedChar ($sEncoding, $cNormalizedChar)
	{
		global $cfg;

		$cNormalizedChar 	= Contenido_Security::escapeDB($cNormalizedChar, $this->_oDB);
		$sEncoding 			= Contenido_Security::escapeDB($sEncoding, $this->_oDB);
		$sEncoding 			= $this->correctEncoding($sEncoding);
		
		if (!array_key_exists($sEncoding, $this->_aCharCache))
		{
			$this->_aCharCache[$sEncoding] = array();	
		}
		
		if (array_key_exists($cNormalizedChar, $this->_aCharCache[$sEncoding]) && count($this->_aCharCache[$cNormalizedChar][$sEncoding]) > 0)
		{
			return $this->_aCharCache[$sEncoding][$cNormalizedChar];
		}
		
		$sql = "SELECT charid FROM ".$cfg["tab"]["chartable"]." WHERE encoding = '".$sEncoding."' AND normalized_char = '".$cNormalizedChar."'";
		$this->_oDB->query($sql);

		$aChars = array();
		
		$this->_aCharCache[$sEncoding][$cNormalizedChar] = array();
		
		while ($this->_oDB->next_record())
		{
			$aChars[] = chr($this->_oDB->f("charid"));
			$this->_aCharCache[$sEncoding][$cNormalizedChar][] = chr($this->_oDB->f("charid"));		
		}
		
		return ($aChars);		
	}
	
	function fetchNormalizedCharsForDiacriticCharacter ($sEncoding, $cCharacter)
	{
		global $cfg;
		
		$cCharacter        	= Contenido_Security::escapeDB($cCharacter, $this->_oDB);
		$sEncoding 			= Contenido_Security::escapeDB($sEncoding, $this->_oDB);
		$sEncoding			= $this->correctEncoding($sEncoding);
		
		if (strlen($cCharacter) > 1)
		{
			cError(__FILE__, __LINE__, "cCharacter is longer than 1 character");	
		}
		
		if (!array_key_exists($sEncoding, $this->_aAliasCache))
		{
			$this->_aAliasCache[$sEncoding] = array();	
		}
		
		if (array_key_exists($cCharacter, $this->_aAliasCache[$sEncoding]) && count($this->_aAliasCache[$sEncoding][$cCharacter]) > 0)
		{
			return $this->_aAliasCache[$sEncoding][$cCharacter];
		}
		
		$sql = "SELECT normalized_char FROM ".$cfg["tab"]["chartable"]." WHERE encoding = '".$sEncoding."' AND charid = '".ord($cCharacter)."'";
		$this->_oDB->query($sql);
		
		$aAliases = array();
		
		$this->_aAliasCache[$sEncoding][$cCharacter] = array();
		
		while ($this->_oDB->next_record())
		{
			$aAliases[] = $this->_oDB->f("normalized_char");
			$this->_aAliasCache[$sEncoding][$cCharacter][] = $this->_oDB->f("normalized_char");		
		}
		
		return ($aAliases);
	}
	
	function correctEncoding ($sEncoding)
	{
		$encodingAliases = array(
							"win-1250" => array("windows-1250", "windows1250", "win-1250"),
							"win-1251" => array("windows-1251", "windows1251", "win-1251"),
							"win-1252" => array("windows-1252", "windows1252", "win-1252"),
							"win-1253" => array("windows-1253", "windows1253", "win-1253"),
							"win-1254" => array("windows-1254", "windows1254", "win-1254"),
							"win-1256" => array("windows-1256", "windows1256", "win-1256"),
							"win-1257" => array("windows-1257", "windows1257", "win-1257"),
							"win-1258" => array("windows-1258", "windows1258", "win-1258"));
							
		foreach ($encodingAliases as $correctAlias => $encodingAlias)
		{
			if (in_array($sEncoding, $encodingAlias))
			{
				$sEncoding = $correctAlias;	
			}	
		}
		
		return $sEncoding;			
	}
}
?>