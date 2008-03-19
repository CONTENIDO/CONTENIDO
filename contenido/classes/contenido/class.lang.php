<?php
/*****************************************
* File      :   $RCSfile: class.lang.php,v $
* Project   :   Contenido
* Descr     :   Language management class
* Modified  :   $Date: 2007/05/25 08:06:29 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.lang.php,v 1.4 2007/05/25 08:06:29 bjoern.behrens Exp $
******************************************/
cInclude('classes', 'class.genericdb.php');


class cApiLanguageCollection extends ItemCollection
{
/**
	 * Constructor
	 */
	function cApiLanguageCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["lang"], "idlang");
		$this->_setItemClass("cApiLanguage");
		$this->_setJoinPartner("cApiClientLanguageCollection");
	}	
}

class cApiLanguage extends Item
{
	function cApiLanguage ($idlang = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["lang"], "idlang");
		
		if ($idlang !== false)
		{
			$this->loadByPrimaryKey($idlang);	
		}
	}
}

?>