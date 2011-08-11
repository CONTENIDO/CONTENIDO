<?php

/**
 * 
 * 
 * This class add a icon for configuration (modul input) in modul output code.
 * It add javascript and the contents of modul input to output code. 
 * Onclick it display a config layer.  
 * @author rusmir.jusufovic
 *
 */

class ModulInputToLayer {
	
	
	/**
	 * 
	 * Marker for calculated input code
	 * @var string
	 */
	private  $_addCalculateCodeMarker = '###ADDHERE';
	/**
	 * 
	 * Marker for input code
	 * @var string
	 */
	private  $_calculateInputCodeMarker = '###REMOVE_LATER';
	/**
	 * 
	 * Template object 
	 * @var Template object
	 */
	private $_tpl = null;
	/**
	 * 
	 * Contenido cfg
	 * @var array
	 */
	private $_cfg = array();
	/**
	 * 
	 * Modul input code
	 * @var string
	 */
	private $_modulCode = '';
	/**
	 * 
	 * Container number of the modul
	 * @var int
	 */
	private $_containerNumber = '';
	
	
	public function __construct($modulCode , $cfg, $containerNumber) {
		
		$this->_tpl = new Template();
		$this->_modulCode = $modulCode;
		$this->_cfg = $cfg;
		$this->_containerNumber = $containerNumber;
		
	}
	
	
	/**
	 * 
	 * Set the template with values (javascript variables..)
	 * @param array $data
	 * @return string javascript and layer 
	 */
	public function addLayerConfig($data) {
		
		$this->_tpl->reset();
		$this->_tpl->set("s","ID_CONFIG_LAYER",$data['idConfigLayer']);
		$this->_tpl->set("s","CONFIG_LABEL",i18n("Konfiguration"));
		$this->_tpl->set("s","MODUL_NAME",$data['modulName']);
		$this->_tpl->set("s","ID_CONFIG_IMAGE",$data['idConfigImage']);
		$this->_tpl->set("s","INPUT_CODE_PLACE_MARKER",$this->_addCalculateCodeMarker);
		$this->_tpl->set("s","ID_TPL",$data['idTpl']);
		$this->_tpl->set("s","ID_TPLCFG",$data['idTplCfg']);
		$this->_tpl->set("s","ID_MOD",$data['idMod']);
		$this->_tpl->set("s","IDENTIFIER",$data['identifier']);
		$this->_tpl->set("s","PATH",$data['path']);
		$this->_tpl->set("s","PATH_AND_JSFILE",$data['pathAndJsFile']);
		$this->_tpl->set("s","SESSION",$data['session']);
		$this->_tpl->set("s","ID_ART_LANG",$data['idArtLang']);
		$this->_tpl->set("s","ID_ART",$data['idArt']);
		$this->_tpl->set("s","ID_CAT",$data['idCat']);
		$this->_tpl->set("s","LANG",$data['lang']);
		$this->_tpl->set("s","CLIENT",$data['client']);
		
		//add to module output javascript and the layer with place for modul input
		$ret = $this->_tpl->generate($this->_cfg['path']['contenido'].$this->_cfg["path"]["templates"].$this->_cfg['templates'] ['config_layer'],true);
		
		//add modul input for calculate 
		$ret .= $this->_calculateInputCodeMarker.$this->_modulCode;
		
		return $ret;	
	}
	
	
	/**
	 * 
	 * Add to output the caluclated input code and remove the marker.
	 * @param string $output
	 * @return string $output 
	 */
	public function removeMarker($output) {
		
	 //remove calculated input code and add it on the right place
     if($this->_modulCode != "") {
                    
     	$removePos = strpos($output,$this->_calculateInputCodeMarker);           	
     	$calculatetdModulInput = substr($output,$removePos+strlen($this->_calculateInputCodeMarker) ,strlen($output));
     	$output = substr($output,0,$removePos);
     	$result = $this->_calculeteInput($calculatetdModulInput);
     	$output = str_replace($this->_addCalculateCodeMarker, $result , $output);
     	
     	return $output;               	
     }  else 
     	return $output;              
	}
	
	/**
	 * 
	 * Calculate the modul input code.
	 * 
	 * @param string $input input code to calculate
	 * @return string calculated code
	 */
	private function _calculeteInput($input) {
 
		//The envirement here and in modul input configuration shuld be the same.
		//We don't know wich global variable input use 
		$db = new DB_Contenido();
		global $sess,$idtplcfg,
				$perm,$idartlang,$idcatart,$encoding,$client,
				$auth,$contenido, $cfg,$cfgClient,$idart,
				$idcat,$client,$lang;
		$cnumber = $this->_containerNumber;
		#Change the name auf CMS_VAR
 		$input  = str_replace("CMS_VAR", "C".$this->_containerNumber."CMS_VAR" , $input);
 
		ob_start();
		eval($input." ");

		$modulecode = ob_get_contents();
		ob_end_clean();
		return $modulecode;
	}
	
}
?>