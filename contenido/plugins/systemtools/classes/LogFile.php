<?php

class LogFile
{
	function LogFile() 
	{
		$this->_bDebug = false;
	}
	
	function logMessage($sMessage, $sLogFile) 
	{
		if ($this->_bDebug) {echo "<pre>logMessage ".$sMessage."</pre>";}
			
	  	if ($file = fopen($sLogFile, "wb"))
	  	{
	  		if ($sMessage[strlen($sMessage)-1] != "\n") 
          	{
         		$sMessage .= "\r\n";
          	}
	      	fwrite($file, $sMessage);
	        fclose($file);
	  	}else
	  	{
	  		return false;
	  	}
	}
	
	function logMessageEnd($sMessage, $sLogFile) 
	{
		if ($this->_bDebug) {echo "<pre>logMessage ".$sMessage."</pre>";}
			
	  	if ($file = fopen($sLogFile, "a+b"))
	  	{
	  		if ($sMessage[strlen($sMessage)-1] != "\n") 
          	{
         		$sMessage .= "\r\n";
          	}
	      	fwrite($file, $sMessage);
	        fclose($file);
	  	}else
	  	{
	  		return false;
	  	}
	}
	
	function logMessageByMode($sMessage, $sLogFile, $sMode = "write_only_begin") 
	{
		if ($this->_bDebug) {echo "<pre>logMessage ".$sMessage."</pre>";}

		/* PHP manual
		'r'  Open for reading only; place the file pointer at the beginning of the file.
		'r+' Open for reading and writing; place the file pointer at the beginning of the file.
		'w'	 Open for writing only; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
		'w+' Open for reading and writing; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
		'a'  Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
		'a+' Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
		*/
		
		switch ($sMode) 
		{
			case "read_only_begin":
			    $sModeOption = "rb";
			    break;
			case "read_write_begin":
			    $sModeOption = "r+b";
			    break;
			case "write_only_begin":
			    $sModeOption = "wb";
			    break;
			case "read_write_begin":
			    $sModeOption = "w+b";
			    break;
			case "write_only_end":
			    $sModeOption = "ab";
			    break;
			case "read_write_end":
			    $sModeOption = "a+b";
			    break;
			default:
			    $sModeOption = "wb";
		}
			
	  	if ($file = fopen($sLogFile, $sModeOption))
	  	{
	  		if ($sMessage[strlen($sMessage)-1] != "\n") 
          	{
         		$sMessage .= "\r\n";
          	}
	      	fwrite($file, $sMessage);
	        fclose($file);
	  	}else
	  	{
	  		return false;
	  	}
	}
	
	function getMessage($sLogFile) 
	{
		if ($this->_bDebug) {echo "<pre>last changed: ".filectime ($sLogFile)."</pre>";}
		
	  	if(file_exists($sLogFile) AND $file = fopen($sLogFile, "rb"))
	  	{
	      	$sMessage = fgets($file);
	        fclose($file);
	        return $sMessage;
	  	}else
	  	{
	  		return NULL;
	  	}
	}
	
	function getLines($sLogFile) 
	{
		if ($this->_bDebug) {echo "<pre>getLines ".$sLogFile."</pre>";}
	
		$aLines = array();
	  	if(is_file($sLogFile))
	  	{
	    	$aLines = file($sLogFile);
	  	}
	  	return $aLines;
	}	
	
	function createFile($sLogFile)
	{
		if ($this->_bDebug) {echo "<pre>createFile ".$sLogFile."</pre>";}
		
		# create  file
	    if(touch($sLogFile))
	    {
	    	# change file access permission
	        if(!chmod ($sLogFile, 0775))
	      	{
				if ($this->_bDebug) {echo "<pre>".$sLogFile." Unable to change file access permission.</pre>";}
	    		return false;
	        }
	    }else
	    {
			if ($this->_bDebug) {echo "<pre>Unable to create file ".$sLogFile."</pre>";}
			return false;
	    }
		return true;
	}
}

?>