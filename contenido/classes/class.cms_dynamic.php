<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Class for handling CMS Dynamic Type
 * With the help of this CMS type, different standard CMS types are handled on a page.
 * 
 * Possible client settings:
 * cms_dynamic_type, start_index_html, <user defined number>		- Start index of CMS_HTML
 * cms_dynamic_type, start_index_img, <user defined number>			- Start index of CMS_IMG
 * cms_dynamic_type, start_index_htmlhead, <user defined number>	- Start index of CMS_HTMLHEAD
 * cms_dynamic_type, start_index_link, <user defined number>		- Start index of CMS_LINK
 * cms_dynamic_template_html, <user defined key>, <template name>	- Start index of CMS_HTML
 * cms_dynamic_template_img, <user defined key>, <template name>	- Start index of CMS_IMG
 * cms_dynamic_template_htmlhead, <user defined key>, <template name> - Start index of CMS_HTMLHEAD
 * cms_dynamic_template_link, <user defined key>, <template name>	- Start index of CMS_LINK
 * 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Content Types
 * @version    1.0.0
 * @author     Munkh-Ulzii Balidar
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.00
 * 
 * {@internal 
 *   created 2011-07-05
 *
 *   $Id$:
 * }}
 * 
 */
class Cms_Dynamic extends AbstractModule{
	
	/**
	 * Index of CMS Dynamic
	 * @var integer
	 */
	private $iId;
	
	/**
	 * Start index of use CMS types in dynamic type
	 * @var array
	 */
	private $iStartIndex = array();
	
	/**
	 * Translation text
	 * @var array
	 */
	private $aTranslations = array();
	
	/**
	 * Type settings as array from XML
	 * @var array
	 */
	private $aSettings = array();
	
	/**
	 * Idartlang of current article
	 * @var integer
	 */
	private $iIdartlang;
	
	/**
	 * Type settings as XML
	 * @var string
	 */
	private $sContent;
	
	/**
	 * Currently used CMS types
	 * @var array
	 */
	private $aElements;
	
	/**
	 * Constructor method
	 */
	public function __construct($sContent, $iId, $iIdartlang) {
		$this->iId = (int)$iId;
		$this->sContent = urldecode($sContent);
		$this->iIdartlang = $iIdartlang;
		$this->iStartIndex['html'] = getEffectiveSetting('cms_dynamic_type', 'start_index_html', 500); 	
		$this->iStartIndex['img'] = getEffectiveSetting('cms_dynamic_type', 'start_index_img', 500); 	
		$this->iStartIndex['htmlhead'] = getEffectiveSetting('cms_dynamic_type', 'start_index_htmlhead', 500); 	
		$this->iStartIndex['link'] = getEffectiveSetting('cms_dynamic_type', 'start_index_link', 500); 	
	
		$this->aElements = array(
            'html' 		=> i18n("HTML"), 
            'htmlhead' 	=> i18n("HTML heading"), 
            'head' 		=> i18n("Heading"), 
            'img'		=> i18n("Image"), 
            'link'		=> i18n("Link")
        );
	}
	
	/**
	 * Start the handling
	 */
	public function start() {
		
		$this->aTranslations = array(
			'title_html'		=> i18n("Edit HTML content"),
			'title_htmlhead'	=> i18n("Edit HTML headng content"),
			'title_link'		=> i18n("Edit link"),
			'title_img'			=> i18n("Edit image"),
			'title_head'		=> i18n("Edit heading content"),
			'text_no_add'		=> i18n("Please select a element before!"),
		);
		
		if (isset($_POST['cms_dynamic_action']) && $_POST['cms_dynamic_action'] != ''
		&& $_POST['cms_dynamic_id'] == $this->iId) {
			switch ($_POST['cms_dynamic_action']) {
				case 'save':
					$this->storeSettings();
					break;
				case 'add':
					$sCmsType = 'CMS_' . strtoupper($_POST['cms_dynamic_add']);
					$iIdtype = $this->getIdType($sCmsType);
					$iNewIndex = $this->findContentTypeid($iIdtype, strtolower($_POST['cms_dynamic_add']), $sCmsType);
					$_POST['cms_dynamic_type'][] = $sCmsType;
					$_POST['cms_dynamic_index'][] = $iNewIndex;
					$this->storeSettings();
					break;	
			}
		}
		
		$this->readSettings();
	}
	
	/**
	 * Show toolbar
	 * 
	 * @return string html
	 */
	public function showToolbar() {
		$cfg = self::getCfg();
		$oTpl = new Template();
		$this->aTranslations['dummy'] = i18n("dummy");
		$trans = json_encode($this->aTranslations);
		$oTpl->set('s', 'TRANSLATIONS', $trans);
		$oTpl->set('s', 'ID', $this->iId);
		
		foreach ($this->aElements as $sName => $sText) {
			$sTemplate = $this->fillTemplateOptions(strtolower($sName));
			$oTpl->set('s', 'OPTIONS_' . strtoupper($sName), $sTemplate);
		}
	
		$oTpl->set('s', 'TEXT_ADD', i18n("Add"));
		$oTpl->set('s', 'TEXT_SAVE', i18n("Save"));
		
		$oTpl->set('s', 'HTML_TEMPLATE', i18n("HTML template"));
		
		// generate form elements
		if (is_array($this->aSettings['elements']) && count($this->aSettings['elements']) > 0) {
			foreach($this->aSettings['elements'] as $iIndex => $aType) {
				$sCont = $this->makeCmsType($aType[0], $aType[1]);
				
				$oTpl->set('d', 'CONTENT_TYPE', $sCont);
				$oTpl->set('d', 'DYN_TYPE', $aType[0]);
				$oTpl->set('d', 'DYN_VALUE', $aType[1]);
				$oTpl->set('d', 'ELEMENT_INDEX', $iIndex);
				$oTpl->next();
			}
		}
		
		foreach($this->aElements as $sName => $sLabel) {
			$oTpl->set('s', 'LINK_NAME_' . strtoupper($sName), $sName);
			$oTpl->set('s', 'LINK_LABEL_' . strtoupper($sName), $sLabel);
		}
		
		$oTpl->set('s', 'CON_PATH', $cfg['path']['contenido_fullhtml']);
		$sCode = $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . 'template.cms_dynamic_edit.html', 1);
		
		return $this->encodeForOutput($sCode);
	}
	
	/**
	 * Show contents
	 * @return string html
	 */
	public function showContent() {
		$cfg = self::getCfg();
		$oTpl = new Template();
		$oTpl1 = new Template();
		
		$oTpl->set('s', 'ID', $this->iId);
		// generate html content
		if (is_array($this->aSettings['elements']) && count($this->aSettings['elements']) > 0) {
			foreach($this->aSettings['elements'] as $iIndex => $aType) {
				$oTpl1->reset();
				
				$sContent = $this->generateContentTemplate($aType[0], $aType[1]);
				
				if (self::getEdit()) {
					switch ($aType[0]) {
						case 'CMS_IMG':
							$aSubTypes = array('CMS_IMGEDIT');
							foreach ($aSubTypes as $sSubType) {
								$sContent .= '<br />' . $this->makeCmsType($sSubType, $aType[1]);
							}
							break;
						case 'CMS_LINK':
							$aSubTypes = array('CMS_LINKEDIT');
							foreach ($aSubTypes as $sSubType) {
								$sContent .= '<br />' . $this->makeCmsType($sSubType, $aType[1]);
							}
							break;	
					}
				}
				
				$oTpl->set('d', 'CONTENT_TYPE', $sContent);
				$oTpl->set('d', 'ELEMENT_INDEX', $iIndex);
				
				$oTpl1->set('s', 'CONTENIDO_PATH', $cfg['path']['contenido_fullhtml']);
				$oTpl1->set('s', 'TEXT_MOVE', i18n("Move"));
				
				foreach ($this->aElements as $sName => $sText) {
					if ('CMS_' . strtoupper($sName) == $aType[0]) {
						$oTpl1->set('s', 'TITLE', $this->aTranslations['title_' . $sName]);
						break;
					}
				}
				
				$oTpl1->set('s', 'title_html', $this->aTranslations['title_']);
				$oTpl1->set('s', 'TEXT_DELETE', i18n("Delete"));
	
				$sAction = (self::getEdit()) ? $oTpl1->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . 'template.cms_dynamic_content_action.html', 1)
											: '';
				
				$oTpl->set('d', 'CONTENT_ACTION', $sAction);
	
				$sIndex =  (self::getEdit()) ? ('<span class="cms_dynamic_element_index_form">cms_dynamic_element_index_' . $iIndex . '</span>') : '';
				$oTpl->set('d', 'FORM_ELEMENT_INDEX', $sIndex);
				$oTpl->next();
			}
		}
		
		$oTpl->set('s', 'BACKEND_CLASS', (self::getEdit()) ? 'cms_dynamic_edit' : '');
		$sCode = $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . 'template.cms_dynamic_content.html', 1);
		
		return $this->encodeForOutput($sCode);
	}
	
	/**
	 * Generate the complete content for an element.
	 * 
	 * @param string $sType
	 * @param integer $iIndex
	 * @return string content
	 */
	private function generateContentTemplate($sType, $iIndex) {
		$sContent = $this->makeCmsType($sType, $iIndex);
		$cfg = self::getCfg();
		$client = self::getClient();
		$cfgClient = self::getCfgClient();
		
		$oTpl = new Template();
		foreach ($this->aElements as $sName => $sLabel) {
			if ($sType == 'CMS_' . strtoupper($sName)) {
				$oTpl->reset();
				switch ($sType) {
					case 'CMS_LINK':
						$oTpl->set('s', 'LINK', $sContent);
						$sTarget = $this->makeCmsType('CMS_LINKTARGET', $iIndex);
						$sTitle = (self::getEdit()) ? $this->makeCmsType('CMS_LINKTITLE', $iIndex) : $this->makeCmsType('CMS_LINKDESCR', $iIndex);
						$oTpl->set('s', 'LINK_TARGET',  $sTarget);
						if (trim($sTitle) == '') {
							$sTitle = $sContent;
						}
						$oTpl->set('s', 'LINK_TITLE',  $sTitle);
						$sContent = $oTpl->generate($cfgClient[$client]['tpl']['path'] . $this->aSettings['configs']['template_' . $sName], 1);
						break;	
					case 'CMS_IMG':
						$oTpl->set('s', 'IMG_PATH', $cfgClient[$client]['upl']['htmlpath'] . $sContent);
						$sImgDescr = (self::getEdit()) ? $this->makeCmsType('CMS_IMGTITLE', $iIndex) : $this->makeCmsType('CMS_IMGDESCR', $iIndex);
						$oTpl->set('s', 'IMG_DESCRIPTION', $sImgDescr);
						$sContent = $oTpl->generate($cfgClient[$client]['tpl']['path'] . $this->aSettings['configs']['template_' . $sName], 1);
						break;
					default:
						$oTpl->set('s', 'CONTENT', $sContent);
						$sContent = $oTpl->generate($cfgClient[$client]['tpl']['path'] . $this->aSettings['configs']['template_' . $sName], 1); 
						break;
				}
			}
		}
		
		return $sContent;
	}
	
	/**
	 * Functen parses XML Document which contains dynamic settings
	 * and store properties as array into $aSettings
	 */
	private function readSettings() {
		//use XMLReader for parsing XML document
		
		if (trim($this->sContent) == '') {
			return false;
		}
		
		$sLastNode = '';
		$oXmlReader = new XMLReader();
		$oXmlReader->XML($this->sContent);
		//store nodename in order to retrive property name when content of node is read
		$sLastNode = '';
		$iAttrIndex = 0;
		while($oXmlReader->read()) {
			switch ($oXmlReader->nodeType) {
			  	//read property name (ignore root node or block of manual arts for dynamic)
				case XMLReader::ELEMENT:
					if ($oXmlReader->name != 'cms_dynamic') {
						$sLastNode = $oXmlReader->name;
						$iAttrIndex = $oXmlReader->getAttribute('index');
					}
					//if we reach <art> node store all subnotes to artnode array
					if ($oXmlReader->name == 'art') {
						$bPutInArtArray = true;
					}
					break;
			  
				//in case of a textnode we have previous propertyname and corrsponding value -> store into aSettings
				//if we were in <art> mode store to corresponding array
				case XMLReader::TEXT:
					
					if ($sLastNode == 'dynamic_element') {
						$this->aSettings['elements'][] = array(Contenido_Security::unfilter($oXmlReader->value), $iAttrIndex);
					} else {
						$this->aSettings['configs'][$sLastNode] = Contenido_Security::unfilter($oXmlReader->value);	
					}
					
					break;
		  }
		}
		return true;
	}

	/**
	 * Function gets all submitted values for new dynamic properties from 
	 * $_POST array, generates new corresponding config XML Document and
	 * stores it as content, using CONTENIDO conSaveContentEntry() function
	 */
	private function storeSettings() {
		//create new xml document, its encoding and root node
		$oDb = self::getDb();
		$cfg = self::getCfg();
		$oXmlDom = new DOMDocument('1.0', 'iso-8859-1');
		$oXmlDom->formatOutput = true;
		$oRootNode = $oXmlDom->createElement('cms_dynamic');
		$oXmlDom->appendChild($oRootNode);
		
		$sTempContent = $this->sContent;
		$aTempSetting = $this->aSettings;
		
		$this->readSettings();
		// delete a removed elements from content table
		if (count($this->aSettings['elements']) > 0) {
			foreach($this->aSettings['elements'] as $iIndex => $aType) {
				$bFound = false;
				if (is_array($_POST['cms_dynamic_type']) && count($_POST['cms_dynamic_type']) > 0) {
					foreach ($_POST['cms_dynamic_type'] as $iIndex => $sType) {
						if ($sType == $aType[0] && $_POST['cms_dynamic_index'][$iIndex] == $aType[1]) {
							$bFound = true;						
						}
					}
				}
				
				if ($bFound === false) {
					$iIdtype = $this->getIdType($aType[0]);
					
					if ($aType[0] == 'CMS_IMG') {
						$sSubElements = " OR idtype=" . $this->getIdType('CMS_IMGDESCR') . " ";
					} elseif ($aType[0] == 'CMS_LINK') {
						$sSubElements = " OR idtype=" . $this->getIdType('CMS_LINKDESCR') . " ";
						$sSubElements .= " OR idtype=" . $this->getIdType('CMS_LINKTARGET') . " ";
					} 
					
					
					$sql = " DELETE FROM " . $cfg["tab"]["content"] . 
							" WHERE (idtype=" . $iIdtype . $sSubElements . " ) AND typeid=" . $aType[1] . 
							" AND idartlang=" . $this->iIdartlang;
	    			$oDb->query($sql);	
				}
				
			}
		}
		
		$this->sContent = $sTempContent;
		$this->aSettings = $aTempSetting;
		
		foreach ($this->aElements as $sName => $sLabel) {
			$sTemplateName = 'template_' . $sName;
			if (isset($_POST[$sTemplateName])) {
				$oElement = $oXmlDom->createElement($sTemplateName, $_POST[$sTemplateName]);
				$oXmlDom->firstChild->appendChild($oElement);
			}
		}
		
		// create new xml content with form data
		if (is_array($_POST['cms_dynamic_type']) && count($_POST['cms_dynamic_type']) > 0) {
			foreach ($_POST['cms_dynamic_type'] as $iIndex => $sType) {
				//generate xml node for current property and store its value
				#$sName = 'elementType_' . Contenido_Security::filter($sType, $oDb);
				$sName = 'dynamic_element';
				$sValue = Contenido_Security::filter($sType, $oDb);
				$oElement = $oXmlDom->createElement($sName, $sValue);
				$oElement->setAttribute('index', $_POST['cms_dynamic_index'][$iIndex]);
				
				$oXmlDom->firstChild->appendChild($oElement);
				
			}	
		}
		
		//serialize xml document and store new version in class variable and database
		conSaveContentEntry($this->iIdartlang, 'CMS_DYNAMIC', $this->iId, $oXmlDom->saveXML(), true);
		$this->sContent = $oXmlDom->saveXML();
		
		//reset con code for this article
		conSetCodeFlag(self::getIdcatArt());
	}
	
	/**
	 * Make a CMS type with giving type and index
	 * 
	 * @param string $containerType
	 * @param int $containerId
	 * @return string
	 */
   	private function makeCmsType($containerType, $containerId) {      
    	global $a_content;
    	$sess = self::getSess();
    	$idart = self::getIdart();
    	$lang = self::getLang();
    	$db = self::getDb();
    	$edit = self::getEdit();
    	$cfg = self::getCfg();
    	$cfgClient = self::getCfgClient();

    	$sql = "SELECT * FROM ".$cfg["tab"]["type"]." WHERE type = '$containerType'";
    	$db->query($sql);

    	$db->next_record();
    	$cms_code = $db->f("code");
    	$cms_idtype = $db->f("idtype");

    	if( !$edit ) {
    		$sql = "SELECT * FROM ".$cfg["tab"]["content"]." AS A, ".$cfg["tab"]["art_lang"]." AS B, ".$cfg["tab"]["type"]." AS C
                    WHERE A.idtype = C.idtype AND A.idartlang = B.idartlang AND B.idart = '".Contenido_Security::toInteger($idart)."' AND B.idlang = '".Contenido_Security::escapeDB($lang, $db)."' AND
                         A.idtype = '".$cms_idtype."' AND A.typeid = '".$containerId."'";
    		$db->query($sql);
    		$db->next_record();
    		$a_content[$db->f("type")][$db->f("typeid")] = $db->f("value");
    	}

    	$val = $containerId;
    	eval($cms_code);
    	$tmp_output = str_replace('\\\"','"',$tmp);
    	$tmp_output = stripslashes($tmp_output);

    	return $tmp_output;
   } 
   
	/**
	 * In CONTENIDO content type code is evaled by php. To make this possible,
	 * this function prepares code for evaluation
	 *
	 * @param string $sCode - code to escape
	 * @return string - escaped code
	 */
	private function encodeForOutput($sCode) {
		$sCode = (string) $sCode;

		$sCode = addslashes($sCode);
		$sCode = str_replace("\\'", "'", $sCode);
		$sCode = str_replace("\$", '\\$', $sCode);

		return $sCode;
	}
	
	/**
	 * Find a new typeid for a content.
	 * Here all the same elements are searched, which were used in the CMS-type dynamic. 
	 * Once an unused ID is returned as the next Id.
	 * 
	 * @param integer $iIdtype
	 * @param string $sType
	 * @param string $sFullType
	 * @return integer new typeid
	 */
	private function findContentTypeid($iIdtype, $sType, $sFullType) {
		$oDb = self::getDb();
		$cfg = self::getCfg();
		
		$sql = " SELECT c.typeid, c.value FROM " . $cfg['tab']['content'] . " as c, " . $cfg['tab']['type'] . " as t " . 
				" WHERE t.type='CMS_DYNAMIC' AND c.idtype=t.idtype AND c.idartlang=". $this->iIdartlang . 
				" ORDER BY c.typeid";
		$oDb->query($sql);
		
		$sTempContent = $this->sContent;
		$sTempSettings = $this->aSettings;
		$aUsedTypeid = array();
		
		while ($oDb->next_record()) {
			$this->aSettings = array();
			$this->sContent = urldecode($oDb->f('value'));
			$this->readSettings();
			
			if (count($this->aSettings['elements'])) {
				foreach ($this->aSettings['elements'] as $aElement) {
					if ($sFullType == $aElement[0]) {
						$aUsedTypeid[] = $aElement[1];
					}
				}
			}
		}
		
		$iIndex = 0;
		$bFound = false;
		$iNewIndex = $this->iStartIndex[$sType];
		
		while (!$bFound) {
			$iNewIndex = $this->iStartIndex[$sType] + $iIndex;
			if (!in_array($iNewIndex, $aUsedTypeid)) {
				$bFound = true;
			}
			$iIndex++;
		}
		
		$this->sContent = $sTempContent;
		$this->aSettings = $sTempSettings;
		return $iNewIndex;	
	
	}
	
	/**
	 * Get the idtype from con_type table for a cms type.
	 * 
	 * @param string $sType
	 * @return integer
	 */
	private function getIdType($sType) {
		$oDb = self::getDb();
		$cfg = self::getCfg();
		
		$sql = " SELECT idtype FROM " . $cfg['tab']['type'] . 
				" WHERE type='". Contenido_Security::escapeDB($sType, $oDB) . "' LIMIT 1";
		$oDb->query($sql);
		if ($oDb->next_record() && (int)$oDb->f('idtype')) {
			return (int)$oDb->f('idtype');
		} else {
			return 0;
		}
	}
	
	/**
	 * Fill the option elements from client settings for frontent view.
	 * Selected template name is read from the saved XML configuration 
	 * and will be selected in the display.
	 * 
	 * @param string $sType
	 * @return string $sOption
	 */
	private function fillTemplateOptions($sType) {
		$sType = strtolower($sType);
		$sCheckedPara = ' selected="selected" ';
		$sOptionPlaceHolder = '<option value="%s" %s>%s</option>';
		
		$sStandardTemplate = 'cms_dynamic_standard_' . $sType . '.html';
		$sSelected = ($this->aSettings['configs']['template_' . $sType] == $sStandardTemplate) ? $sCheckedPara : '';
		$sOption = sprintf($sOptionPlaceHolder, $sStandardTemplate, $sSelected, i18n("Default template")); 
		
		$aTemplates = getEffectiveSettingsByType('cms_dynamic_template_' .  strtolower($sType));
		if (is_array($aTemplates) && count($aTemplates)) {
			foreach ($aTemplates as $sTypeName => $sTemplateName) {
				if ($sTypeName != '' && $sTemplateName != '') {
					$sSelected = ($this->aSettings['configs']['template_' . $sType] == $sTemplateName) ? $sCheckedPara : '';
					$sOption .= sprintf($sOptionPlaceHolder, $sTemplateName, $sSelected, $sTypeName);					
				}
			}
		}
		
		return $sOption;		
	}
	
 	public function print_rr($var) {
 		echo "<pre>";
 		print_r($var);
 		echo "</pre>";
 	}
}