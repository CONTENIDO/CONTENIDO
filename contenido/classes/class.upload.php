<?php

/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Module history
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.9
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-14
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2008-10-03, Oliver Lohkemper, modified UploadCollection::delete()
 *   modified 2008-10-03, Oliver Lohkemper, add CEC in UploadCollection::store()
 *
 *   $Id: class.upload.php 858 2008-10-20 07:26:28Z OliverL $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('classes', 'class.dbfs.php');

class UploadCollection extends ItemCollection
{
	/**
     * Constructor Function
     * @param none
     */
	function UploadCollection ()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["upl"], "idupl");
	}
	
	function sync ($dir, $file)
	{
		global $client;

		
		if (strstr(strtolower($_ENV["OS"]), 'windows') === FALSE) {
			#Unix  style OS distinguish between lower and uppercase file names, i.e. test.gif is not the same as Test.gif 
			$this->select("dirname = BINARY '$dir' AND filename = BINARY '$file' AND idclient = '$client'");
		} else {
			#Windows OS doesn't distinguish between lower and uppercase file names, i.e. test.gif is the same as Test.gif in file system
			$this->select("dirname = '$dir' AND filename = '$file' AND idclient = '$client'");
		}
		
		if ($item = $this->next())
		{
			$item->update();
		} else {
			$this->create($dir, $file);
		}
	}
	
	function create ($dir, $file)
	{
		global $client, $cfg, $auth;
		
		$item = parent::create();
	
		$item->set("idclient", $client);
		$item->set("filename", $file, false);
		$item->set("dirname", $dir, false);
		$item->set("author", $auth->auth["uid"]);
		$item->set("created", date("Y-m-d H:i:s"),false);
		$item->store();
		
		$item->update();
		
		return ($item);	
		
	}
	
	function loadItem ($itemID)
	{
		$item = new UploadItem();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function delete ($id)
	{
		global $_cecRegistry, $cfgClient, $client;
		$item = new UploadItem();
		$item->loadByPrimaryKey($id);
	   
		/*
		* Call chain
		*/
		$_cecIterator = $_cecRegistry->getIterator("Contenido.Upl_edit.Delete");
		if ($_cecIterator->count() > 0) {
			while ($chainEntry = $_cecIterator->next()) {
				$chainEntry->execute( $item->get('idupl'), $item->get("dirname"), $item->get("filename") );
		}   }
	   
		/*
		* delete from Filesystem or DBFS
		*/
		if( is_dbfs($item->get("dirname").$item->get("filename")) ) {
			$dbfs = new DBFSCollection;
			$dbfs->remove($item->get("dirname").$item->get("filename"));
		}
		else {
		  if(file_exists($cfgClient[$client]["upl"]["path"].$item->get("dirname").$item->get("filename"))) {
			unlink( $cfgClient[$client]["upl"]["path"].$item->get("dirname").$item->get("filename") );
		  }
		}
	   
		/*
		* delete in DB
		*/
		return parent::delete($id);
	} 
}

class UploadItem extends Item
{

	/**
     * Constructor Function
     * @param $id int Specifies the ID to load
     */
	function UploadItem ()
	{
		global $cfg;
		parent::Item($cfg["tab"]["upl"], "idupl");
	}
	
	
	function update ()
	{
		global $client, $cfgClient;
		
		if (substr($this->get("dirname"),0,5) == "dbfs:")
		{
			$isdbfs = true;
			$dir = $this->get("dirname");
		} else {
			$isdbfs = false;
			$dir = $cfgClient[$client]["upl"]["path"].$this->get("dirname");
		}
		
		$file = $this->get("filename");
		
		$dbfs = new DBFSCollection;
		
		$fullfile = $dir.$file;
    	/* Strip the file extension */
    	$dotposition = strrpos($file, ".");
    	
    	if ($dotposition !== false)
    	{
    		$extension = substr($file, $dotposition + 1);
    	}
    	
    	if ($isdbfs)
		{
			$filesize = $dbfs->getSize($fullfile);
		} else {
			if (file_exists($fullfile))
			{
				$filesize = filesize($fullfile);
			}
		}
    	
    	$touched = false;
    	
    	if ($this->get("filetype") != $extension)
    	{
    		$this->set("filetype", $extension);
    		$touched = true;
    	}
    	
    	if ($this->get("size") != $filesize)
    	{
    		$this->set("size", $filesize);
    		$touched = true;
    	}

		if ($touched == true)
		{
    		$this->store();
		}
	}
	
	function store ()
	{
		global $auth, $_cecRegistry;
		
		$this->set("modifiedby", $auth->auth["uid"]);
		$this->set("lastmodified", date("Y-m-d H:i:s"),false);
		
		/*
		* Call chain
		*/
		$_cecIterator = $_cecRegistry->getIterator("Contenido.Upl_edit.SaveRows");
		if ($_cecIterator->count() > 0) {
			while ($chainEntry = $_cecIterator->next()) {
				$chainEntry->execute( $this->get("idupl"), $this->get("dirname"), $this->get("filename") );
		}   }
	  
		parent::store();
	}
	
}



?>
