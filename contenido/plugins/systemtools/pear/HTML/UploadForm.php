<?php
/**
 * Upload Form experimental
 *  
 * @file: UploadForm.php
 * @created: 29.04.2005
 * @modified: 29.04.2005
 * 
 * @version	1.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 */

# NOTE: this class is experimental

include_once 'Form.php';

class UploadForm
{
	/**
	 * @var object of type HTML_Form 
     * @access public
	 */
	var $oForms;
	/**
	 * @var boolean 
     * @access private
	 */	 
	var $_bDebug;
	/**
	 * @var Contenido global
     * @access public
	 */	
	var $client;
	/**
	 * @var Contenido global
     * @access public
	 */	
	var $lang;
	/**
	 * @var Contenido global
     * @access public
	 */	
	var $cfg;
	/**
	 * @var Contenido global
     * @access public
	 */	
	var $cfgClient;
	
	/**
     * Constructor
     */
	function UploadForm($action, $method, $name, $enctype, $cfg, $cfgClient, $client, $lang) 
	{
		#$action = 'main.php', $method = 'POST', $name = 'event_action', $enctype = 'multipart/form-data'
		$this->oForms = new HTML_Form($action, $method, $name, $target = '', $enctype);
		$this->client = $client;
		$this->lang = $lang;
		$this->cfg = $cfg;
		$this->cfgClient = $cfgClient;
		$this->_bDebug = false;	
	}
	
	/**
     * Get HMTL Upload Form
     * @param array 
     * @param string 
     * 
     * @access public
     * @return string HTML.
     */	
	function getUploadForm($aHiddenInputs, $iMaxFilesize, $sSize = '40', $sArea, $sFrame, $sAction, $sSess)
	{
		#define("__MAXFILESIZE", 2097152);
		
		$aHiddenInputs = array("area" => $sArea, "area" => $sArea, "area" => $sArea, "area" => $sArea);
		
		$sHiddenInputs = $this->oForms->returnHidden("area", $sArea);
		$sHiddenInputs .= $this->oForms->returnHidden("frame", $sFrame);
		$sHiddenInputs .= $this->oForms->returnHidden("eventaction", $sAction);
		$sHiddenInputs .= $this->oForms->returnHidden("save_actions", "true");
		$sHiddenInputs .= $this->oForms->returnHidden("contenido", $sSess);
		$sHiddenInputs .= $this->oForms->returnHidden("idevent", $sIdEvent);
		
	   	$this->oForms->returnFile('image[]', $iMaxFilesize, $sSize, '', 'class="text_medium"');
	   	$this->oForms->returnImage('store1', 'images/submit.gif', 'title="'.$sTitle.'"');
			
		return $this->oForms->returnStart() . $sHiddenInputs .  $this->oForms->returnEnd();
	}
	
}

?>