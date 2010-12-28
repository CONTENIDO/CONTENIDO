<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Area management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.15
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-02-26
 *   
 *	 modified 2010-08-17, Munkh-Ulzii Balidar,
 *		- changed SQL query in method moduleInUse
 *		- added new property aUsedTemplates and saved the information of used templates 
 *		- added new method getUsedTemplates
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class cApiModuleCollection extends ItemCollection
{	
	/**
     * Constructor Function
     * @param none
     */
	function cApiModuleCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["mod"], "idmod");
		
		$this->_setItemClass("cApiModule");
	}

	/**
     * Creates a new communication item
     */		
	function create ($name)
	{
		global $auth, $client;
		$item = parent::create();
		
		$item->set("idclient", $client);
		$item->set("name", $name);
		$item->set("author", $auth->auth["uid"]);
		$item->set("created", date("Y-m-d H:i:s"),false);
		$item->store();
		return $item;
	}
}

/**
 * Module access class
 */
class cApiModule extends Item
{
	var $_error;
	
	var $_packageStructure;
	
	/**
	 * @var array
	 */
	private $aUsedTemplates = array();
		
    /**
     * Constructor Function
     * @param $loaditem Item to load
     */
    function cApiModule($loaditem = false)
    {
		global $cfg, $cfgClient, $client;
		parent::Item($cfg["tab"]["mod"], "idmod");
		
		// Using no filters is just for compatibility reasons.
		// That's why you don't have to stripslashes values if you store them
		// using ->set. You have to add slashes, if you store data directly
		// (data not from a form field)
		$this->setFilters(array(), array());
		
		if ($loaditem !== false)
		{
			$this->loadByPrimaryKey($loaditem);	
		}
		
		$this->_packageStructure = array("jsfiles"  => $cfgClient[$client]["js"]["path"], 
										 "tplfiles" => $cfgClient[$client]["tpl"]["path"],
										 "cssfiles" => $cfgClient[$client]["css"]["path"]);
		
    } // end function

	function loadByPrimaryKey ($id)
	{
		if (parent::loadByPrimaryKey($id)) {
    		if ($this->_shouldLoadFromFiles()) {
    			global $cfg;
    			$sRootPath = $cfg['path']['contenido'] . $cfg['path']['modules'] . $this->get("idclient")."/" . $this->get("idmod").".xml";

    			if (file_exists($sRootPath)) {
    				$this->import($sRootPath);		
    			}
    		}
            return true;
        }
        return false;
        
	}

	/**
     * getTranslatedName
	 * Returns the translated name of the module if
	 * a translation exists.
	 *
     * @param none
	 * @return string Translated module name or original
     */		
	function getTranslatedName ()
	{
		global $lang;
		
		/* If we're not loaded, return */
    	if ($this->virgin == true)
    	{
    		return false;
    	}		
		
		$modname = $this->getProperty("translated-name", $lang);
		
		if ($modname === false)
		{
			return $this->get("name");	
		} else {
			return $modname;
		}
	}
	
	/**
     * setTranslatedName
	 * Sets the translated name of the module
	 *
     * @param $name string Translated name of the module
	 * @return none
     */		
	function setTranslatedName ($name)
	{
		global $lang;
		
		$this->setProperty("translated-name", $lang, $name);	
	}

	/**
     * parseModuleForStrings
	 * Parses the module for mi18n strings and returns
	 * them in an array
	 *
	 * @return array Found strings for this module
     */		
    function parseModuleForStrings ()
    {
    	if ($this->virgin == true)
    	{
    		return false;
    	}
    	
		/* Fetch the code, append input to output */
		$code  = $this->get("output");
		$code .= $this->get("input");
		
		/* Initialize array */
    	$strings = array();
    	
    	/* Split the code into mi18n chunks */
    	$varr = preg_split('/mi18n([\s]*)\(([\s]*)"/', $code, -1);
    	
		if (count($varr) > 1)
    	{
        	foreach ($varr as $key => $value)
        	{
	        	/* Search first closing */
	        	$closing = strpos($value,'")');
	        	
	        	if ($closing === false)
	        	{
	        		$closing = strpos($value,'" )');	
	        	}
	        	
	        	if ($closing !== false)
	        	{
	        		$value = substr($value,0, $closing).'")';
	        	}
	        	
	        	/* Append mi18n again */
	        	$varr[$key] = 'mi18n("'.$value;
	        
	        	/* Parse for the mi18n stuff */
	        	preg_match_all('/mi18n([\s]*)\("(.*)"\)/', $varr[$key], $results);
	    		
	    		/* Append to strings array if there are any results */
	    		if (is_array($results[1]) && count($results[2]) > 0)
	    		{
	    			$strings = array_merge($strings, $results[2]);	
	    		}
	    		
	    		/* Unset the results for the next run */
	        	unset($results);
	        }
    	}
		
		// adding dynamically new module translations by content types
		// this function was introduced with contenido 4.8.13
		
		// checking if array is set to prevent crashing the module translation page
		if ( is_array( $cfg['translatable_content_types'] ) && count ( $cfg['translatable_content_types'] ) > 0 ) {
			// iterate over all defines cms content types
			foreach ( $cfg['translatable_content_types'] as $sContentType ) {
				// check if the content type exists and include his class file
				if ( file_exists ( $cfg['contenido']['path'] . "classes/class." . strtolower ( $sContentType ) . ".php" ) ) {
					cInclude("classes", "class." . strtolower ( $sContentType ) . ".php" );
					// if the class exists, has the method "addModuleTranslations" 
					// and the current module contains this cms content type we 
					// add the additional translations for the module
					if ( class_exists ( $sContentType ) && 
						method_exists( $sContentType, 'addModuleTranslations' ) && 
						preg_match('/' . strtoupper ( $sContentType ) . '\[\d+\]/', $code) ) {
						
						$strings = call_user_func(array($sContentType, 'addModuleTranslations'), $strings);
					}
				}
			}
		}
        
        /* Make the strings unique */
        return array_unique($strings);
    }

    /**
     * moduleInUse()
     * Checks if the module is in use
     * @return bool    Specifies if the module is in use
     */
    function moduleInUse( $module, $bSetData = false)
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    c.idmod, c.idtpl, t.name 
                FROM
                ". $cfg["tab"]["container"] . " as c, 
                ". $cfg["tab"]["tpl"] . " as t 
                WHERE
                    c.idmod = '" . Contenido_Security::toInteger($module) . "' AND 
                    t.idtpl=c.idtpl  
                GROUP BY c.idtpl 
                ORDER BY t.name";
        $db->query($sql);

        if ($db->nf() == 0)
        {
            return false;
        } else {
	        $i = 0;
        	// save the datas of used templates in array
        	if ($bSetData === true) {
	        	while ($db->next_record()) {
	        		$this->aUsedTemplates[$i]['tpl_name'] = $db->f('name');
	        		$this->aUsedTemplates[$i]['tpl_id'] = (int)$db->f('idmod');
	        		$i++;
	        	}        		
        	}
        	
            return true;
        }
    } // end function  
    
    /**
     * Get the informations of used templates
     * @return array template data
     */
    public function getUsedTemplates()
    {
    	return $this->aUsedTemplates;
    }

    /**
     * isOldModule()
     * Checks if the module is a pre-4.3 module
     * @return boolean true if this module is an old one
     */
	function isOldModule ()
	{
		/* Keywords to scan */
		$scanKeywords = array('$cfgTab', 'idside', 'idsidelang');
		
		$input  = $this->get("input");
		$output = $this->get("output");
		
		foreach ($scanKeywords as $keyword)
		{
			if (strstr($input, $keyword))
			{
				return true;	
			}
			
			if (strstr($output, $keyword))
			{
				return true;
			}
		}
	}
	
	function getField ($field)
	{
		$value = parent::getField($field);
		
		switch ($field)
		{
			case "name":
				if ($value == "")
				{
					$value = i18n("- Unnamed Module -");	
				}	
		}
		
		return ($value);
	}
	    
    function store ($bJustStore = false)
    {
    	global $cfg;
    	
    	if ($bJustStore)
    	{
    		// Just store changes, e.g. if specifying the mod package
    		parent::store();
    	} else
    	{
    		cInclude("includes", "functions.con.php");
    		
	    	parent::store();
	    	
			conGenerateCodeForAllArtsUsingMod($this->get("idmod"));
	    	
	    	if ($this->_shouldStoreToFile())
	    	{
	    		if ($this->_makeFileDirectoryStructure())
	    		{
		    		$sRootPath = $cfg['path']['contenido'] . $cfg['path']['modules'] . $this->get("idclient")."/";
		    		file_put_contents($sRootPath . $this->get("idmod").".xml", $this->export($this->get("idmod").".xml", true));
	    		}
	    	}
    	}
    }
    
    function _makeFileDirectoryStructure ()
    {
    	global $cfg;
    	
    	$sRootPath = $cfg['path']['contenido'] . $cfg['path']['modules']; 
    	if (!is_dir($sRootPath))
    	{
    		@mkdir($sRootPath);
    	}
    	
    	$sRootPath = $cfg['path']['contenido'] . $cfg['path']['modules'] . $this->get("idclient")."/";
    	if (!is_dir($sRootPath))
    	{
    		@mkdir($sRootPath);	
    	}
    	
    	if (is_dir($sRootPath))
    	{
    		return true;	
    	} else {
    		return false;	
    	}
    	
    }
    
    function _shouldStoreToFile ()
    {
    	if (getSystemProperty("modules", "storeasfiles") == "true")
    	{
    		return true;	
    	} else {
    		return false;	
    	}
    }
    
    function _shouldLoadFromFiles ()
    {
    	if (getSystemProperty("modules", "loadfromfiles") == "true")
    	{
    		return true;	
    	} else {
    		return false;	
    	}
    }
    
    /** 
     *  _parseImportFile
     *  Parse import xml file, stores data in global variable (-> event handler functions)
     *  @access private
     * 
     * 	@param string $sFile Filename including path of import xml file
     * 	@param string $sType Import type, "module" or "package"
     * 
     *  @return bool Returns true, if file has been parsed 
     */
    function _parseImportFile ($sFile, $sType = "module", $sEncoding = "ISO-8859-1")
    {
		global $_mImport;
		
		$oParser = new XmlParser($sEncoding);
		
		if ($sType == "module")
		{
			$oParser->setEventHandlers(array("/module/name"=> "cHandler_ModuleData",
											"/module/description"=> "cHandler_ModuleData",
											"/module/type"=> "cHandler_ModuleData",
											"/module/input" => "cHandler_ModuleData",
											"/module/output" => "cHandler_ModuleData"));
		} else {
			$aHandler = array("/modulepackage/guid" 			=> "cHandler_ModuleData",
							  #"/modulepackage/repository_guid"	=> "cHandler_ModuleData",
							  "/modulepackage/module/name"		=> "cHandler_ModuleData",
							  "/modulepackage/module/description" => "cHandler_ModuleData",
							  "/modulepackage/module/type"		=> "cHandler_ModuleData",
							  "/modulepackage/module/input"		=> "cHandler_ModuleData",
							  "/modulepackage/module/output"	=> "cHandler_ModuleData",
							  "/modulepackage/module/input"		=> "cHandler_ModuleData");
			
			// Add file handler (e.g. js, css, templates)
			foreach ($this->_packageStructure As $sFileType => $sFilePath)
			{
				// Note, that $aHandler["/modulepackage/" . $sFileType] and using
				// a handler which uses the node name (here: FileType) doesn't work,
				// as the event handler for the filetype node will be fired
				// after the node has been successfully parsed, not before.
				// So, we have a little redundancy here, but maybe we need
				// this in the future.
				$aHandler["/modulepackage/" . $sFileType . "/area"] 	= "cHandler_ItemArea";
				$aHandler["/modulepackage/" . $sFileType . "/name"] 	= "cHandler_ItemName";
				$aHandler["/modulepackage/" . $sFileType . "/content"]	= "cHandler_ItemData";
			}
			
			// Layouts
			$aHandler["/modulepackage/layouts/area"] 		= "cHandler_ItemArea";
			$aHandler["/modulepackage/layouts/name"] 		= "cHandler_ItemName";
			$aHandler["/modulepackage/layouts/description"]	= "cHandler_ItemData";
			$aHandler["/modulepackage/layouts/content"] 	= "cHandler_ItemData";
			
			// Translations
			$aHandler["/modulepackage/translations/language"] 			= "cHandler_ItemArea";
			$aHandler["/modulepackage/translations/string/original"] 	= "cHandler_ItemName";
			$aHandler["/modulepackage/translations/string/translation"]	= "cHandler_Translation";
		
			$oParser->setEventHandlers($aHandler);
		}

		
		if ($oParser->parseFile($sFile))
		{
			return true;
		} else {
			$this->_error = $oParser->error;
			return false;	
		}
	}

	/**
	 * import
     * Imports the a module from a XML file
     * Uses xmlparser and callbacks
	 *
	 * @param string	$file 	Filename of data file (full path) 
     */
	function import ($sFile)
    {
    	global $_mImport;
    
    	if ($this->_parseImportFile($sFile, "module"))
    	{
    		$bStore = false;
			foreach ($_mImport["module"] as $key => $value)
			{
				if ($this->get($key) != $value)
				{
					$this->set($key, addslashes($value));
					$bStore = true;
				}
			}
			
			if ($bStore == true)
			{
				$this->store();
			}
			return true;
    	} else {
    		return false;	
    	}
    }

	/**
	 * export
     * Exports the specified module strings to a file
	 *
	 * @param $filename string Filename to return
	 * @param $return	boolean if false, the result is immediately sent to the browser 
     */    
    function export ($filename, $return = false)
    {
    	$tree  = new XmlTree('1.0', 'ISO-8859-1');
    	$root =& $tree->addRoot('module');

		$root->appendChild("name", htmlspecialchars($this->get("name")));    		
		$root->appendChild("description", htmlspecialchars($this->get("description")));
		$root->appendChild("type", htmlspecialchars($this->get("type")));
		$root->appendChild("input", htmlspecialchars($this->get("input")));
		$root->appendChild("output", htmlspecialchars($this->get("output")));
		
    	if ($return == false)
    	{
			header("Content-Type: text/xml");
    		header("Etag: ".md5(mt_rand()));
    		header("Content-Disposition: attachment;filename=\"$filename\"");
    		$tree->dump(false);
    	} else {
    		return stripslashes($tree->dump(true));	
    	}	
    }
    	
	function getPackageOverview($sFile)
	{
		global $_mImport;
		
		if ($this->_parseImportFile($sFile, "package"))
		{
			$aData = array();
			$aData["guid"] 				= $_mImport["module"]["guid"];
			$aData["repository_guid"]	= $_mImport["module"]["repository_guid"];
			$aData["name"]				= $_mImport["module"]["name"];
			
			// Files
			foreach ($this->_packageStructure as $sFileType => $sFilePath)
			{
				if (is_array($_mImport["items"][$sFileType]))
				{
					$aData[$sFileType] = array_keys($_mImport["items"][$sFileType]);
				}	
			}

			// Layouts
			if (is_array($_mImport["items"]["layouts"]))
			{
				$aData["layouts"] = array_keys($_mImport["items"]["layouts"]);
			}
			
			// Translation languages
			if (is_array($_mImport["translations"]))
			{
				$aData["translations"] = array_keys($_mImport["translations"]);
			}
			
			return $aData;
		} else {
			return false;
		}
	}
	
	/**
	 * importPackage
	 * Imports a module package from a XML file
	 * Uses xmlparser and callbacks
	 *
	 * @param string	$sFile 		Filename of data file (including path)
	 * @param array		$aOptions	Optional. An array of arrays specifying, how the items 
	 * 								of the xml file will be imported. If specified, has to 
	 * 								contain an array of this structure:
	 * 
	 * $aOptions["items"][<filetype>][<htmlspecialchars(filename)>]				= "skip", "append" or "overwrite";
	 * $aOptions["translations"][<PackageLanguage>]	= <AssignedIDLang>;
	 * 
	 * 								If a file is not mentioned in the $aOptions["items"][<filetype>]
	 * 								array, it is new and will be imported.
	 * 
	 * 								If a <PackageLang> is not found in $aOptions["translations"],
	 * 								then the translations for this language will be ignored
	 * 
	 * @return bool Returns true, if import has been successfully finished
	 */
	function importPackage ($sFile, $aOptions = array())
	{
		global $_mImport, $client;
		
		cInclude("includes", "functions.file.php");
		cInclude("includes", "functions.lay.php"); // You won't believe the code in there (or what is missing in class.layout.php...)
		
		// Ensure correct options structure
		foreach ($this->_packageStructure as $sFileType => $sFilePath)
		{
			if (!is_array($aOptions["items"][$sFileType]))
			{
				$aOptions["items"][$sFileType] = array();
			}
		}
		
		// Layouts
		if (!is_array($aOptions["items"]["layouts"]))
		{
			$aOptions["items"]["layouts"] = array();
		}
		
		// Translations
		if (!is_array($aOptions["translations"]))
		{
			$aOptions["translations"] = array();
		}
		
		// Parse file
		if ($this->_parseImportFile($sFile, "package"))
		{
			// Import data					
			// Module
			foreach ($_mImport["module"] as $sKey => $sData)
			{
				if ($this->get($sKey) != $sData)
				{
					$this->set($sKey, addslashes($sData));
					$bStore = true;
				}
			}
			
			if ($bStore == true)
			{
				$this->store();
			}
			
			// Files
			foreach ($this->_packageStructure as $sFileType => $sFilePath)
			{
				if (is_array($_mImport["items"][$sFileType]))
				{
					foreach ($_mImport["items"][$sFileType] as $sFileName => $aContent)
					{
						if (!array_key_exists(htmlspecialchars($sFileName), $aOptions["items"][$sFileType]) || 
							$aOptions["items"][$sFileType][htmlspecialchars($sFileName)] == "overwrite")
						{
							if (!file_exists($sFilePath . $sFileName))
							{
								createFile($sFileName, $sFilePath);
							}
							fileEdit($sFileName, $aContent["content"], $sFilePath);
						} else if ($aOptions["items"][$sFileType][htmlspecialchars($sFileName)] == "append")
						{
							$sOriginalContent = getFileContent($sFileName, $sFilePath);
							
							fileEdit($sFileName, $sOriginalContent . $aContent["content"], $sFilePath);
						}
					}
				}	
			}

			// Layouts
			if (is_array($_mImport["items"]["layouts"]))
			{
				foreach ($_mImport["items"]["layouts"] as $sLayout => $aContent)
				{
					if (!array_key_exists(htmlspecialchars($sLayout), $aOptions["items"]["layouts"]) || 
						$aOptions["items"]["layouts"][htmlspecialchars($sLayout)] == "overwrite")
					{
						$oLayouts = new cApiLayoutCollection;
						$oLayouts->setWhere("idclient", $client);
						$oLayouts->setWhere("name", $sLayout);
						$oLayouts->query();
						
						if (!$oLayout = $oLayouts->next())
						{
							layEditLayout(false, addslashes($sLayout), addslashes($aContent["description"]), addslashes($aContent["content"]));
						} else {
							layEditLayout($oLayout->get($oLayout->primaryKey), addslashes($sLayout), addslashes($aContent["description"]), addslashes($aContent["content"]));
						}						
					} else if ($aOptions["items"]["layouts"][htmlspecialchars($sLayout)] == "append")
					{
						$oLayouts = new cApiLayoutCollection;
						$oLayouts->setWhere("idclient", $client);
						$oLayouts->setWhere("name", $sLayout);
						$oLayouts->query();
						
						if (!$oLayout = $oLayouts->next())
						{
							layEditLayout(false, addslashes($sLayout), addslashes($aContent["description"]), addslashes($aContent["content"]));
						} else {
							layEditLayout($oLayout->get($oLayout->primaryKey), addslashes($sLayout), addslashes($oLayout->get("description") . $aContent["description"]), addslashes($oLayout->get("code") . $aContent["content"]));
						}
					}
				}
			}
			
			// Translations
			if (is_array($_mImport["translations"]))
			{
				$oTranslations	= new cApiModuleTranslationCollection;
				$iID 			= $this->get($this->primaryKey);
								
				foreach ($_mImport["translations"] as $sPackageLang => $aTranslations)
				{
					if (array_key_exists($sPackageLang, $aOptions["translations"]))
					{
						foreach ($_mImport["translations"][$sPackageLang] as $sOriginal => $sTranslation)
						{
							$oTranslations->create($iID, $aOptions["translations"][$sPackageLang], 
												   $sOriginal, $sTranslation);
						}
					}
				}
			}
			return true;
		} else {
			return false;
		}
	}

	
	/**
	 * exportPackage
	 * Exports the specified module and attached files to a file
	 *
	 * @param string	$sPackageFileName	Filename to return
	 * @param bool		$bReturn			if false, the result is immediately sent to the browser 
	 */	
	function exportPackage ($sPackageFileName, $bReturn = false)
	{
		global $cfgClient, $client;
		
		cInclude("includes", "functions.file.php");
				
		$oTree = new XmlTree('1.0', 'ISO-8859-1');
		$oRoot =& $oTree->addRoot('modulepackage');
		
		$oRoot->appendChild("package_guid", $this->get("package_guid"));
		$oRoot->appendChild("package_data", $this->get("package_data")); // This is serialized and more or less informal data

		$aData = unserialize($this->get("package_data"));
		if (!is_array($aData))
		{
			$aData = array();
			$aData["repository_guid"]	= "";
			$aData["jsfiles"]			= array();
			$aData["tplfiles"]			= array();
			$aData["cssfiles"]			= array();
			$aData["layouts"]			= array();
			$aData["translations"]		= array();
		}

		// Export basic module
		$oNodeModule =& $oRoot->appendChild("module");
		$oNodeModule->appendChild("name",		 htmlspecialchars($this->get("name")));			
		$oNodeModule->appendChild("description", htmlspecialchars($this->get("description")));
		$oNodeModule->appendChild("type", 		 htmlspecialchars($this->get("type")));
		$oNodeModule->appendChild("input", 		 htmlspecialchars($this->get("input")));
		$oNodeModule->appendChild("output", 	 htmlspecialchars($this->get("output")));
		
		// Export files (e.g. js, css, templates)
		foreach ($this->_packageStructure As $sFileType => $sFilePath)
		{
			$oNodeFiles =& $oRoot->appendChild($sFileType);
			if (count($aData[$sFileType]) > 0)
			{
				foreach ($aData[$sFileType] as $sFileName)
				{
					if (is_readable($sFilePath . $sFileName))
					{
						$sContent = getFileContent($sFileName, $sFilePath);
						
						$oNodeFiles->appendChild("area", 	htmlspecialchars($sFileType));
						$oNodeFiles->appendChild("name", 	htmlspecialchars($sFileName));
						$oNodeFiles->appendChild("content", htmlspecialchars($sContent));
					}
				}
			}
		}
		unset ($sContent);
		
		// Export layouts
		$oNodeLayouts =& $oRoot->appendChild("layouts");
		
		$oLayouts = new cApiLayoutCollection;
		$oLayouts->setWhere("idclient", $client);
		$oLayouts->query();
		
		while ($oLayout = $oLayouts->next())
		{
			if (in_array($oLayout->get($oLayout->primaryKey), $aData["layouts"]))
			{
				$oNodeLayouts->appendChild("area", 			"layouts");
				$oNodeLayouts->appendChild("name", 			htmlspecialchars($oLayout->get("name")));
				$oNodeLayouts->appendChild("description",	htmlspecialchars($oLayout->get("description")));
				$oNodeLayouts->appendChild("content",		htmlspecialchars($oLayout->get("code")));
			}
		}
		unset ($oLayout);
		unset ($oLayouts);
		
		// Export translations
		$oLangs = new cApiLanguageCollection;
		$oLangs->setOrder("idlang");
		$oLangs->query();
	
		if ($oLangs->count() > 0)
		{
			$iIDMod = $this->get($this->primaryKey);
			while ($oLang = $oLangs->next())
			{
				$iID = $oLang->get($oLang->primaryKey);
				
				if (in_array($iID, $aData["translations"]))
				{
					$oNodeTrans =& $oRoot->appendChild("translations");
					// This is nice, but it doesn't help so much,
					// as this data is available too late on import ...
	   				$oNodeTrans->setNodeAttribs(array("origin-language-id" => $iID,
	   									   			  "origin-language-name" => htmlspecialchars($oLang->get("name"))));
					// ... so we store the important information with the data
					$oNodeTrans->appendChild("language", htmlspecialchars($oLang->get("name")));
					
					$oTranslations = new cApiModuleTranslationCollection;
					$oTranslations->setWhere("idmod", $iIDMod);
					$oTranslations->setWhere("idlang", $iID);
					$oTranslations->query();
							   									   
					while ($oTranslation = $oTranslations->next())
					{
						$oNodeString =& $oNodeTrans->appendChild("string");
			
						$oNodeString->appendChild("original",		htmlspecialchars($oTranslation->get("original")));
						$oNodeString->appendChild("translation",	htmlspecialchars($oTranslation->get("translation")));
					}
				}
			}
		}
		unset ($oLangs);
		unset ($oLang);
		
		if ($bReturn == false)
		{
			header("Content-Type: text/xml");
			header("Etag: ".md5(mt_rand()));
			header("Content-Disposition: attachment;filename=\"$sPackageFileName\"");
			$oTree->dump(false);
		} else {
			return stripslashes($oTree->dump(true));	
		}	
	}			
} // end class

class cApiModuleTranslationCollection extends ItemCollection
{
	var $_error;
		
	/**
	 * Constructor Function
	 * @param none
	 */
	function cApiModuleTranslationCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["mod_translations"], "idmodtranslation");
		
		$this->_setItemClass("cApiModuleTranslation");
	}

	/**
	 * Creates a new module translation item
	 */		
	function create ($idmod, $idlang, $original, $translation = false)
	{
		/* Check if the original already exists. If it does, 
		   update the translation if passed */
		$mod = new cApiModuleTranslation;
		$sorg = $mod->_inFilter($original);
		
		$this->select("idmod = '$idmod' AND idlang = '$idlang' AND original = '$sorg'");
		
		if ($item = $this->next())
		{
			if ($translation !== false)
			{
				$item->set("translation", $translation);
				$item->store();
			}
			return $item;	
		} else {			
			$item = parent::create();
			$item->set("idmod", $idmod);
			$item->set("idlang", $idlang);
			$item->set("original", $original);
			$item->set("translation", $translation);
			$item->store();		
			return $item;
		}
	}
	
	/**
	 * fetchTranslation
	 * Fetches a translation
	 *
	 * @param $module int Module ID
	 * @param $lang   int Language ID
	 * @param $string string String to lookup
	 */
	function fetchTranslation ($module, $lang, $string)
	{
		/* If the f_obj does not exist, create one */
		if (!is_object($this->f_obj))
		{
			$this->f_obj = new cApiModuleTranslation;
		}
		
		/* Create original string */
		$sorg = $this->f_obj->_inFilter($string);
		
		/* Look up */
		$this->select("idmod = '$module' AND idlang='$lang' AND original = '$sorg'");
		
		if ($t = $this->next())
		{
			$translation = $t->get("translation");
			
			if ($translation != "")
			{
				return $translation;
			} else {
				return $string;
			}
		} else {
			return $string;
		}
	}
	
	function import ($idmod, $idlang, $file)
	{
		global $_mImport;
		
		$parser = new XmlParser("ISO-8859-1");
			
		$parser->setEventHandlers(array("/module/translation/string/original"=> "cHandler_ItemName",
										"/module/translation/string/translation"=> "cHandler_Translation"));  

		$_mImport["current_item_area"] = "current"; // Pre-specification, as this won't be set from the XML file (here)

		if ($parser->parseFile($file))
		{
			foreach ($_mImport["translations"]["current"] as $sOriginal => $sTranslation)
			{
				$this->create ($idmod, $idlang, $sOriginal, $sTranslation);
			}
			
			return true;
			
		} else {
			$this->_error = $parser->error;
			return false;	
		}
	}
	/**
	 * export
	 * Exports the specified module strings to a file
	 *
	 * @param $idmod	int Module ID
	 * @param $idlang   int Language ID
	 * @param $filename string Filename to return
	 * @param $return	boolean if false, the result is immediately sent to the browser 
	 */	
	function export ($idmod, $idlang, $filename, $return = false)
	{
		$langobj = new Language;
		$langobj->loadByPrimaryKey($idlang);
		
		$langstring = $langobj->get("name") . ' ('.$idlang.')';
		
		$translations = new cApiModuleTranslationCollection;
		$translations->select("idmod = '$idmod' AND idlang='$idlang'");
		
		$tree  = new XmlTree('1.0', 'ISO-8859-1');
		$root =& $tree->addRoot('module');
			
		$translation =& $root->appendChild('translation');
	   	$translation->setNodeAttribs(array("origin-language-id" => $idlang,
	   									   "origin-language-name" => $langobj->get("name")));
		   									   
		while ($otranslation = $translations->next())
		{
			$string =&$translation->appendChild("string");
			
			$string->appendChild("original", htmlspecialchars($otranslation->get("original")));
			$string->appendChild("translation", htmlspecialchars($otranslation->get("translation")));	
		}	   									   
		
		if ($return == false)
		{
			header("Content-Type: text/xml");
			header("Etag: ".md5(mt_rand()));
			header("Content-Disposition: attachment;filename=\"$filename\"");
			$tree->dump(false);
		} else {
			return $tree->dump(true);	
		}	
	}	
}

/**
 * Module access class
 */
class cApiModuleTranslation extends Item
{
	/**
	 * Constructor Function
	 * @param $loaditem Item to load
	 */
	function cApiModuleTranslation($loaditem = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["mod_translations"], "idmodtranslation");
		
		if ($loaditem !== false)
		{
			$this->loadByPrimaryKey($loaditem);	
		}
	} // end function
} // end class

function cHandler_ModuleData($sName, $aAttribs, $sContent)
{
	global $_mImport;
	
	$_mImport["module"][$sName] = $sContent;
}

// The following three functions references all file data (e.g. for css, 
// js and template files) and layout data
// Note, that first the type is specified (from the "area" information 
// in the xml file).
// Second, filename is specified based on "name" node content.
// Third, file content is stored using type, name and node content.
// You will have to specify individual handler functions, if one of 
// the file areas may store additional data (e.g. a description) 
function cHandler_ItemArea($sName, $aAttribs, $sContent)
{
	global $_mImport;
	
	$_mImport["current_item_area"] = $sContent;
}

function cHandler_ItemName($sName, $aAttribs, $sContent)
{
	global $_mImport;
	
	$_mImport["current_item_name"] = $sContent;
}

function cHandler_ItemData($sName, $aAttribs, $sContent)
{
	global $_mImport;
	
	$_mImport["items"][$_mImport["current_item_area"]][$_mImport["current_item_name"]][$sName] = $sContent;
}

// Separate language area, as someone may specify "cssfiles" or something 
// as language name, funny guy...
function cHandler_Translation($sName, $aAttribs, $sContent)
{
	global $_mImport;
	
	$_mImport["translations"][$_mImport["current_item_area"]][$_mImport["current_item_name"]] = $sContent;
}

?>