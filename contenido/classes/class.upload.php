<?php

/*****************************************
* File      :   $RCSfile: class.upload.php,v $
* Project   :   Contenido
* Descr     :   Module history
*
* Author    :   Timo A. Hummel
*               
* Created   :   14.12.2003
* Modified  :   $Date: 2006/04/28 09:20:55 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.upload.php,v 1.7 2006/04/28 09:20:55 timo.hummel Exp $
******************************************/

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
			$dir = $cfgClient[$client]["upl"]["path"].$this->get("dirname");
			$isdbfs = false;
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
		global $auth;
		
		$this->set("modifiedby", $auth->auth["uid"]);
		$this->set("lastmodified", date("Y-m-d H:i:s"),false);
		
		parent::store();	
	}
	
}



?>
