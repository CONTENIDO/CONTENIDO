<?php

class Cronjobs {
	
	
	/**
	 * 
	 * All Contenido vars (contenido,lang,cfg,cfgClients ...)
	 * @var array
	 */
	protected  $_conVars = array();
	
	/**
	 * 
	 * The name of cronjobs directory 
	 * @var string
	 */
	public static $NAME_OF_CRONJOB_DIR = 'cronjobs';
	
	public static $CRONTAB_FILE = 'crontab.txt';
	
	public static $JOB_ENDING = '.job';
	
	public static $LOG_ENDING = '.log';
	
	protected $_phpFile = '';
	
	/**
	 * 
	 * Filename without the mimetype
	 * @var string
	 */
	private $_fileName = '';
	
	protected $_cfg = array();

	protected $_cronjobDirectory = '';
	public function __construct(array $contenidoVars, $phpFile = '') {
		
		$this->_conVars = $contenidoVars;
		$this->_phpFile = $phpFile;
		//get the name of the file withouth the mime type
		if($phpFile != '')
			$this->_fileName = substr($phpFile,0,-4);
		
		$cfg = $this->_conVars['cfg'];
		$this->_cronjobDirectory = $cfg['path']['contenido'].self::$NAME_OF_CRONJOB_DIR.'/';
		
	}
	
	/**
	 * 
	 * Return the name of file 
	 * @return string filename
	 */
	public function getFile() {
		
		return $this->_phpFile;
	}
	
	/**
	 * 
	 * Get the directory path of cronjobs
	 * @return string 
	 */
	public function getDirectory () {
		
		return $this->_cronjobDirectory;
	}
	
	
	/**
	 * 
	 * Get date of last execution of cronjob
	 * @return date
	 */
	public function getDateLastExecute() {
		
		$timestamp = ''; 
		if(file_exists($this->_cronjobDirectory.$this->_phpFile.self::$JOB_ENDING)) 
		if(($timestamp = file_get_contents($this->_cronjobDirectory.$this->_phpFile.self::$JOB_ENDING))) {
			return date("d.m.Y H:i:s",$timestamp);	
		}
		return $timestamp;
	}
	
	
	/**
	 * Get the contents of the crontab.txt file
	 * 
	 * @return string, contents of the file or ''
	 */
	public function getContentsCrontabFile() {
		
		if(file_exists($this->_cronjobDirectory.self::$CRONTAB_FILE)) {
			
			return file_get_contents($this->_cronjobDirectory.self::$CRONTAB_FILE);
		}else 
			return '';
	}
	
	/**
	 * 
	 * Save the data to crontab.txt file
	 * @param string $data
	 * @return mixed file_put_contents
	 */
	public function saveCrontabFile($data) {
		
		return file_put_contents($this->_cronjobDirectory.self::$CRONTAB_FILE, $data);	
		
	}
	
	/**
	 * 
	 * Set the execute-time to $this->_phpFile.job file.
	 * @param int $timestamp
	 */
	public function setRunTime($timestamp) {
		
		file_put_contents($this->_cronjobDirectory.$this->_phpFile.self::$JOB_ENDING,$timestamp);
	}
	
	
	/**
	 * 
	 * Get the last lines of log file
	 * @param int $lines
	 * @return string, the lines
	 */
	public function getLastLines($lines = 25) {
		
		if(file_exists($this->_cronjobDirectory.$this->_phpFile.self::$LOG_ENDING)) {
			$content = explode("\n", file_get_contents($this->_cronjobDirectory.$this->_phpFile.self::$LOG_ENDING));
			$number = count($content);
        	$pos = $number - $lines;
        	if ($pos < 0) {
                $lines += $pos;
                $pos = 0;
        	}

        	return implode('<br>',array_slice($content, $pos, $lines));
		}
		
		return '';
	}
	/**
	 * Exist the file and is it a php file
	 * 
	 * @return bool if exist 
	 */
	public function existFile() {
        
		if(file_exists($this->_cronjobDirectory.$this->_phpFile) && !is_dir($this->_cronjobDirectory.$this->_phpFile))
			if(substr($this->_phpFile,-4)=='.php')
				return true;
			else 
				return false;
		else 
			return false;
	}
	/**
	 * 
	 * Get all Cronjobs in directory cronjobs from contenido
	 */
	public function getAllCronjobs() {
		
		$retArray = array();
       
        if (is_dir($this->_cronjobDirectory)) {
            if ($dh = opendir($this->_cronjobDirectory)) {
                while (($file = readdir($dh))!== false) {
                    #is file a dir or not
                    if ($file!= ".."&& $file!= "."&& !is_dir($this->_cronjobDirectory. $file. "/") && substr($file,-4)=='.php' && $file != 'index.php') {
                        
                        $retArray[] = $file;
                    }
                }
            }
        }
        return $retArray;
        
	}
	
}

?>