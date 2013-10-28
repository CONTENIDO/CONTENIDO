<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Class for handling CMS Type Teaser
 * Teaser is able to teaser all articles in a category. It is also possible to set a list of articles
 * which were displayed as manual teaser. This function is not category dependant. There are also
 * several more properties like sort defintions, character limits for teaser text and a teaser headline
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Content Types
 * @version    1.0.5
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.12
 *
 * {@internal
 *   created 2009-04-08
 *   modified 2009-04-14 - added possibility to expand template select by client or system setting using type 'cms_teaser'
 *   modofied 2009-04-21 - added seperate handling for xhtml
 *   modified 2009-05-04 - added sort order sequence
 *   modified 2009-10-01 - Dominik Ziegler, fixed session bug in link
 *   modified 2009-10-12 - Dominik Ziegler, fixed online/offline articles, dynamic teaser generation and translation implemented
 *   modified 2009-10-16 - Dominik Ziegler, added manual date support
 *   modified 2010-01-21 - Dominik Ziegler, strip tags from manual teaser date
 *
 *   $Id$:
 * }}
 *
 */


if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.api.images.php');

/**
 * Class handles content type teaser, its editmode and viewmode. All properties of teaser content type
 * were stored as xml document in database as content in {prefix}_content
 *
 */
class Cms_Teaser {
	/**
	 * Contenido configuration array
	 *
	 * @var array
	 * @access private
	 */
	private $aCfg;

	/**
	 * Current id of content type CMS_TEASER[3] -> 3
	 *
	 * @var integer
	 * @access private
	 */
	private $iId;

	/**
	 * Contenido database object
	 *
	 * @var object
	 * @access private
	 */
	private $oDb;

	/**
	 * Idartlang of article, which is currently in edit- or viewmode
	 *
	 * @var integer
	 * @access private
	 */
	private $iIdArtLang;

	/**
	 * List of fieldnames in frontend (properties) which the teaser has
	 * and which were also described in the config xml document
	 *
	 * @var array
	 * @access private
	 */
	private $aTeaserData;

	/**
	 * String contains value of stored content in database
	 * in this case this is the config xml document which is
	 * later parsed and its settings were stored in $aSettings
	 *
	 * @var string
	 * @access private
	 */
	private $sContent;

	/**
	 * Array which contains current teaser settings
	 *
	 * @var array
	 * @access private
	 */
	private $aSettings;

	/**
	 * Array which contains all avariable CMS_Types and its ids
	 * in current Contenido isntallation (described as hash [idtype => cmstypename])
	 *
	 * @var array
	 * @access private
	 */
	private $aCMSTypes;

	/**
	 * current Contenido client id
	 *
	 * @var integer
	 * @access private
	 */
	private $iClient;

	/**
	 * current Contenido language id
	 *
	 * @var integer
	 * @access private
	 */
	private $iLang;

	/**
	 * Contenido Session object
	 *
	 * @var object
	 * @access private
	 */
	private $oSess;

	/**
	 * Contenido configuration array for currently active client
	 *
	 * @var array
	 * @access private
	 */
	private $aCfgClient;

	/**
	 * print XHTML
	 *
	 * @var string
	 * @access private
	 */
	private $sUseXHTML;

	/**
	 * Placeholders for labels in frontend.
	 * Important: This must be a static array!
	 * @var		array
	 * @access	private
	 */
	private static $aTranslations = array("MORE" => "mehr");

	/**
	 * Constructor of class inits some important class variables and
	 * gets some Contenido global vars, so this class has no need to
	 * use ugly and buggy global commands
	 *
	 * @param string $sContent - xml document from database containing teaser settings
	 * @param integer $iNumberOfCms - CMS_TEASER[4] => 4
	 * @param integer $iIdArtLang - Idartlang of current article
	 * @param array $sEditLink - sEditlink for editbuttons, not currently used
	 * @param array $aCfg - Contenido configuration array
	 * @param array $oDB - Contenido database object (not used because we need own object (else problems by cross query in same object))
	 * @param string $sContenidoLang - Contenido Backend language string
	 * @param integer $iClient - Contenido client id
	 * @param integer $iLang - Contenido frontend language id
	 * @param array $aCfgClient - Contenido Client configuration array
	 *
	 * @access public
	 */
	function __construct($sContent, $iNumberOfCms, $iIdArtLang, $sEditLink, $aCfg, $oDB, $sContenidoLang, $iClient, $iLang, $aCfgClient, $oSess) {
		//set arguments to class variables directly
		$this->aCfg = $aCfg;
		$this->iId = $iNumberOfCms;
		$this->iIdArtLang = $iIdArtLang;
		$this->sContent = urldecode($sContent);
		$this->iClient = $iClient;
		$this->iLang = $iLang;
		$this->aCfgClient = $aCfgClient;
		$this->oSess = $oSess;

		if (!array_key_exists("generate_xhtml", $aCfg)) {
			$this->sUseXHTML = getEffectiveSetting("generator", "xhtml", 'false');
		} else {
			$this->sUseXHTML = $aCfg['generate_xhtml'];
		}

		//init other variables with default values
		$this->aCMSTypes = null;
		$this->aSettings = array();
		$this->oDb = new DB_Contenido();

		//define class array which contains all names of teaser properties. They were also base for generating dynamic javascripts for
		//retrival this properties out of html forms and retriving their values to screen
		$this->aTeaserData = array('teaser_title', 'teaser_category', 'teaser_count', 'teaser_style', 'teaser_manual',
		                           'teaser_start', 'teaser_source_head', 'teaser_source_head_count', 'teaser_source_text',
								   'teaser_source_text_count', 'teaser_source_image', 'teaser_source_image_count', 'teaser_filter',
								   'teaser_sort', 'teaser_sort_order', 'teaser_character_limit', 'teaser_image_width',
								   'teaser_image_height', 'teaser_manual_art', 'teaser_image_crop', 'teaser_source_date',
								   'teaser_source_date_count');

		//if form is submitted there is a need to store current teaser settings
		//notice: there is also a need, that teaser_id is the same (case: more than ohne cms teaser is used on the same page
		if (isset($_POST['teaser_action']) && $_POST['teaser_action'] == 'store' &&
		    isset($_POST['teaser_id']) && (int)$_POST['teaser_id'] == $this->iId) {
			$this->storeTeaser();
		}

		//in sContent XML Document is stored, which contains teaser settings, call function which parses this document and store
		//properties as easy accessible array into $aSettings
		if (trim($this->sContent) != '') {
			$this->readSettings();
		}

		$this->setDefaultValues();
	}

	/**
	 * Returns all translation strings for mi18n.
	 *
	 * @param	array	$aTranslationStrings	Array with translation strings
	 * @return	array	Translation strings
	 */
	static public function addModuleTranslations($aTranslationStrings) {
		foreach(self::$aTranslations as $sValue) {
			$aTranslationStrings[] = $sValue;
		}

		return $aTranslationStrings;
	}

	/**
	 * Functen parses XML Document which contains teaser settings
	 * and store properties as array into $aSettings
	 *
	 * @access private
	 */
	private function readSettings() {
		//use XMLReader for parsing XML document
		$oXmlReader = new XMLReader();
		$oXmlReader->XML($this->sContent);
		//store nodename in order to retrive property name when content of node is read
		$sLastNode = '';
		//in xml document there is a block <art> in this artids for manual teaser were stored
		//if we were in this block store all values to an art array
		//this variable defines if we were in this block or not
		$bPutInArtArray = false;
		//array for previous described manual art array
		$this->aSettings['teaser_manual_art'] = array();

		while($oXmlReader->read()) {
			switch ($oXmlReader->nodeType) {
			  //read property name (ignore root node or block of manual arts for teaser)
			  case XMLReader::ELEMENT:
			  if ($oXmlReader->name != 'teaser' && $oXmlReader->name != 'manual_art' && $oXmlReader->name != 'art') {
					$sLastNode = 'teaser_'.$oXmlReader->name;
					$this->aSettings[$sLastNode] = '';
			  }
			  //if we reach <art> node store all subnotes to artnode array
			  if ($oXmlReader->name == 'art') {
				$bPutInArtArray = true;
			  }
			  break;

			  //in case of a textnode we have previous propertyname and corrsponding value -> store into aSettings
			  //if we were in <art> mode store to corresponding array
			  case XMLReader::TEXT:
				if ($bPutInArtArray == true) {
					$bPutInArtArray = false;
					array_push($this->aSettings['teaser_manual_art'], Contenido_Security::unfilter($oXmlReader->value));
				} else {
					$this->aSettings[$sLastNode] = utf8_decode(Contenido_Security::unfilter($oXmlReader->value));
				}
				break;
		  }
		}
	}

	/**
	 * Function gets all submitted values for new teaser properties from
	 * $_POST array, generates new corresponding config XML Document and
	 * stores it as content, using contenido conSaveContentEntry() function
	 *
	 * @access private
	 */
	private function storeTeaser() {
		//create new xml document, its encoding and root node
		$oXmlDom = new DOMDocument('1.0', 'iso-8859-1');
		$oXmlDom->formatOutput = true;
		$oRootNode = $oXmlDom->createElement('teaser');
		$oXmlDom->appendChild($oRootNode);

		//$this->aTeaserData defines all teaser properties, so try to read them from %_POST
		foreach ($this->aTeaserData as $sParam) {
			//in case of article list for manual teaser do a special behaviour
		    if ($sParam == 'teaser_manual_art') {
				$oParam = $oXmlDom->createElement(str_replace('teaser_', '', $sParam));
				//split all arts to array
				$aArts = explode(';', Contenido_Security::toString($_POST[$sParam]));
				//for each artid generate subnote in xml document and store its value
				foreach ($aArts as $iArt) {
					$iArt = (int) $iArt;
					if ($iArt > 0) {
						$oArt = $oXmlDom->createElement('art', $iArt);
						$oParam->appendChild($oArt);
					}
				}
			} else {
				//generate xml node for current property and store its value
				$oParam = $oXmlDom->createElement(str_replace('teaser_', '', $sParam), utf8_encode($_POST[$sParam]));
			}

			$oXmlDom->firstChild->appendChild($oParam);
		}

		//serialize xml document and store new version in class variable and database
		conSaveContentEntry($this->iIdArtLang, 'CMS_TEASER', $this->iId, $oXmlDom->saveXML(), true);
		$this->sContent = $oXmlDom->saveXML();
	}

	/**
	 * Function which generated a select box for setting number of articles which were
	 * displayed in teaser as a maximum. Only important in editmode
	 *
	 * @param string $sSelected - value of select box which is selected
	 * @return html string of select box
	 *
	 * @access private
	 */
	private function getCountSelect($sSelected) {
		$this->oDb = new DB_Contenido();

		$oHtmlSelect = new 	cHTMLSelectElement ('teaser_count', "", 'teaser_count');

		//set please chose option element
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		//generate a select box containing count 1 to 20 for maximum teaser count
		for ($i = 1; $i <= 20; $i++) {
			$oHtmlSelectOption = new cHTMLOptionElement($i, $i, false);
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
		}

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}


	/**
	 * Function which generated a select box for setting teaser style
	 * currently two seperate teaser templates were supported
	 *
	 * @param string $sSelected - value of select box which is selected
	 * @return html string of select box
	 *
	 * @access private
	 */
	private function getStyleSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('teaser_style', "", 'teaser_style');

		//set please chose option element
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		//set other avariable options manually
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Block style"), 'cms_teaser_style_block.html', false);
		$oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Blog style"), 'cms_teaser_style_blog.html', false);
		$oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);

		$aAdditionalOptions = getEffectiveSettingsByType('cms_teaser');
		$i = 3;
		foreach ($aAdditionalOptions as $sLabel => $sTemplate) {
			$oHtmlSelectOption = new cHTMLOptionElement($sLabel, $sTemplate, false);
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
			$i++;
		}

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

	/**
	 * Function which generated a select box for setting teaser
	 * sort order argument
	 *
	 * @param string $sSelected - value of select box which is selected
	 * @return html string of select box
	 *
	 * @access private
	 */
	private function getSortOrderSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('teaser_sort_order', "", 'teaser_sort_order');

		//set please chose option element
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		//set other avariable options manually
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Ascending"), 'asc', false);
		$oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Descending"), 'desc', false);
		$oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

    /**
	 * Function which provides select option for cropping teaser images
	 *
	 * @param string $sSelected - value of select box which is selected
	 * @return html string of select box
	 *
	 * @access private
	 */
	private function getCropSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('teaser_image_crop', "", 'teaser_image_crop');

		//set please chose option element
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		//set other avariable options manually
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Scaled"), 'false', false);
		$oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Cropped"), 'true', false);
		$oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

	/**
	 * Function which generated a select box for setting teaser
	 * sort argument
	 *
	 * @param string $sSelected - value of select box which is selected
	 * @return html string of select box
	 *
	 * @access private
	 */
	private function getSortSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('teaser_sort', "", 'teaser_sort');

		//set please chose option element
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		//set other avariable options manually
		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Sort sequence"), 'sortsequence', false);
		$oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Creationdate"), 'creationdate', false);
		$oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Publisheddate"), 'publisheddate', false);
		$oHtmlSelect->addOptionElement(3, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Modificationdate"), 'modificationdate', false);
		$oHtmlSelect->addOptionElement(4, $oHtmlSelectOption);

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

	/**
	 * Function which gets all currenty avariable content types and their ids
	 * from database and store it into class variable aCMSTypes. Because this
	 * information is used multiple, this way causes a better performance to get
	 * this information seperately
	 *
	 * @access private
	 */
	private function initCmsTypes() {
		$this->aCMSTypes = array();

		$sSql = 'SELECT * from %s ORDER BY type';

		$this->oDb->query(sprintf($sSql, $this->aCfg['tab']['type']));

		while ($this->oDb->next_record()) {
			$this->aCMSTypes[$this->oDb->f('idtype')] = $this->oDb->f('type');
		}
	}

	/**
	 * Teaser gets informations from other articles and their content typs
	 * Function builds a select box in which coresponding cms type can be selected
	 * after that a text box is rendered for setting id for this conent type to get
	 * informations from. This function is used three times for source defintion of
	 * headline text and teaserimage
	 *
	 * @param unknown_type $sSelectName - name of input elements
	 * @param string $sSelected - value of select box which is selected
	 * @param string $sValue - current value of text box
	 * @return html string of select box
	 *
	 * @access private
	 */
	private function getTypeSelect($sSelectName, $sSelected, $sValue) {
		//generate textbox for content type id
		$oHtmlInput = new cHTMLTextbox ($sSelectName.'_count', $sValue, "", "", $sSelectName.'_count');

		//generate content type select
		$oHtmlSelect = new cHTMLSelectElement ($sSelectName, "", $sSelectName);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		//use $this->aCMSTypes as basis for this select box which contains all avariable content types in system
		foreach ($this->aCMSTypes as $sKey => $sValue) {
			$oHtmlSelectOption = new cHTMLOptionElement($sValue, $sValue, false);
			$oHtmlSelect->addOptionElement($sKey, $oHtmlSelectOption);
		}

		$oHtmlSelect->setStyle("width:147px;");

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		$oHtmlInput->setStyle("width:50px;");

		return $oHtmlSelect->render().$oHtmlInput->render();
	}

	/**
	 * Function is called in editmode of contenido an returns teaser view and editbutton
	 *
	 * @return string - escaped html code for further use in contenido and sending to browser
	 *
	 * @access public
	 */
	public function getAllWidgetEdit() {
		$this->initCmsTypes();

		$oTpl = new Template();
		/*Set some values into javascript for a better handling*/
		$oTpl->set('s', 'CON_PATH', $this->aCfg['path']['contenido_fullhtml']);
		$oTpl->set('s', 'ID', $this->iId);
		$oTpl->set('s', 'IDARTLANG', $this->iIdArtLang);
		$oTpl->set('s', 'CONTENIDO', $_REQUEST['contenido']);
		//output fields for use in javascript
		$oTpl->set('s', 'FIELDS', "'".implode("','",$this->aTeaserData)."'");

		/*Start set a lot of translations*/
		$oTpl->set('s', 'LABEL_TEASERSETTINGS', i18n("Teasersettings"));
		$oTpl->set('s', 'LABEL_TEASERTITLE', i18n("Teasertitle"));
		$oTpl->set('s', 'LABEL_START', i18n("Teaser startarticle"));
		$oTpl->set('s', 'LABEL_TARGET', i18n("Sourcecategory"));
		$oTpl->set('s', 'LABEL_COUNT', i18n("Number of articles"));
		$oTpl->set('s', 'LABEL_GENERAL', i18n("General settings"));
		$oTpl->set('s', 'LABEL_STYLE', i18n("Teaser style"));
		$oTpl->set('s', 'LABEL_ADVANCED', i18n("Advanced teaser settings"));
		$oTpl->set('s', 'LABEL_FILTER', i18n("Teaser filter"));
		$oTpl->set('s', 'LABEL_SORT', i18n("Teaser sort"));
		$oTpl->set('s', 'LABEL_SORT_ORDER', i18n("Sort order"));
		$oTpl->set('s', 'LABEL_SOURCEHEAD', i18n("Source headline"));
		$oTpl->set('s', 'LABEL_SOURCE', i18n("Source settings"));
		$oTpl->set('s', 'LABEL_SOURCETEXT', i18n("Source text"));
		$oTpl->set('s', 'LABEL_SOURCEIMAGE', i18n("Source image"));
		$oTpl->set('s', 'LABEL_SOURCEDATE', i18n("Source date"));

		$oTpl->set('s', 'LABEL_CAT', i18n("Category"));
		$oTpl->set('s', 'LABEL_ART', i18n("Article"));

		$oTpl->set('s', 'GENERAL', i18n("General"));
		$oTpl->set('s', 'ADVANCED', i18n("Advanced"));
		$oTpl->set('s', 'MANUAL', i18n("Manual"));
		$oTpl->set('s', 'LABEL_EXISTING_ARTICLES', i18n("Included articles"));
		$oTpl->set('s', 'LABEL_ADD_ARTICLE', i18n("Add article"));
		$oTpl->set('s', 'LABEL_MANUAL', i18n("Manual teaser settings"));
		$oTpl->set('s', 'LABEL_USE_MANUAL', i18n("Manual teaser"));


		$oTpl->set('s', 'SIZE_SETTINGS', i18n("Size settings"));
		$oTpl->set('s', 'LABEL_CHARACTER_LIMIT', i18n("Character length"));
		$oTpl->set('s', 'LABEL_IMAGE_WIDTH', i18n("Image width"));
		$oTpl->set('s', 'LABEL_IMAGE_HEIGHT', i18n("Image height"));
        $oTpl->set('s', 'LABEL_IMAGE_CROP', i18n("Image scale"));
		/*End set a lot of translations*/

		/*Start set values into configuration array and generate select boxes used previous defined values CASE CHECKBOXES*/
		if ($this->aSettings['teaser_start'] == 'true') {
			$oTpl->set('s', 'START_CHECKED', 'checked');
		} else {
			$oTpl->set('s', 'START_CHECKED', '');
		}

		if ($this->aSettings['teaser_manual'] == 'true') {
			$oTpl->set('s', 'MANUAL_CHECKED', 'checked');
		} else {
			$oTpl->set('s', 'MANUAL_CHECKED', '');
		}
		/*Start set values into configuration array and generate select boxes used previous defined values CASE CHECKBOXES*/

		/*Start set values into configuration array and generate select boxes used previous defined values*/
		$sCatSelect = buildCategorySelect('teaser_category', $this->aSettings['teaser_category'], 0);

		$oTpl->set('s', 'TARGET_SELECT', $sCatSelect);
		$oTpl->set('s', 'CAT_SELECT', buildCategorySelect('teaser_cat', 0, 0));
		$oTpl->set('s', 'ART_SELECT', buildArticleSelect('teaser_art', 0, 0));
		$oTpl->set('s', 'COUNT_SELECT', $this->getCountSelect($this->aSettings['teaser_count']));
		$oTpl->set('s', 'STYLE_SELECT', $this->getStyleSelect($this->aSettings['teaser_style']));
		$oTpl->set('s', 'SOURCEHEAD_SELECT', $this->getTypeSelect('teaser_source_head', $this->aSettings['teaser_source_head'], $this->aSettings['teaser_source_head_count']));
		$oTpl->set('s', 'SOURCETEXT_SELECT', $this->getTypeSelect('teaser_source_text', $this->aSettings['teaser_source_text'], $this->aSettings['teaser_source_text_count']));
		$oTpl->set('s', 'SOURCEIAMGE_SELECT', $this->getTypeSelect('teaser_source_image', $this->aSettings['teaser_source_image'], $this->aSettings['teaser_source_image_count']));
		$oTpl->set('s', 'SOURCEDATE_SELECT', $this->getTypeSelect('teaser_source_date', $this->aSettings['teaser_source_date'], $this->aSettings['teaser_source_date_count']));
		$oTpl->set('s', 'TEASER_TITLE', $this->aSettings['teaser_title']);
		$oTpl->set('s', 'FILTER_VALUE', $this->aSettings['teaser_filter']);
		$oTpl->set('s', 'SORT_SELECT', $this->getSortSelect($this->aSettings['teaser_sort']));
		$oTpl->set('s', 'SORT_ORDER_SELECT', $this->getSortOrderSelect($this->aSettings['teaser_sort_order']));
        $oTpl->set('s', 'IMAGE_CROP_SELECT', $this->getCropSelect($this->aSettings['teaser_image_crop']));
		$oTpl->set('s', 'CHARACTER_LIMIT', $this->aSettings['teaser_character_limit']);
		$oTpl->set('s', 'IMAGE_WIDTH', $this->aSettings['teaser_image_width']);
		$oTpl->set('s', 'IMAGE_HEIGHT', $this->aSettings['teaser_image_height']);
		$oTpl->set('s', 'LABEL_ADD', i18n("Add"));

		$sOptions = '';
		if (is_array($this->aSettings['teaser_manual_art'])) {
			foreach ($this->aSettings['teaser_manual_art'] as $iIdArt) {
				$sOptions .= '<option value="'.$iIdArt.'">'.$this->getArtName($iIdArt).'</option>'."\n";
			}
		}
		$oTpl->set('s', 'MANUAL_OPTIONS', $sOptions);
		/*End set values into configuration array and generate select boxes used previous defined values*/

		$sCode = $oTpl->generate($this->aCfg['path']['contenido'].'templates/standard/template.cms_teaser_edit.html', 1);

		//return $this->encodeForOutput($sCode);

		return $this->getAllWidgetView( true ) . $this->encodeForOutput($sCode);
	}

	/**
	 * In Contenido content type code is evaled by php. To make this possible,
	 * this function prepares code for evaluation
	 *
	 * @param string $sCode - code to escape
	 * @return string - escaped code
	 *
	 * @access private
	 */
	private function encodeForOutput($sCode) {
		$sCode = (string) $sCode;

		$sCode = AddSlashes(AddSlashes($sCode));
		$sCode = str_replace("\\\'", "'", $sCode);
		$sCode = str_replace("\$", '\\\$', $sCode);

		return $sCode;
	}

	/**
	 * Function sets some default values for teaser in case that there is no
	 * value definied
	 *
	 * @access private
	 */
	private function setDefaultValues() {
		//character limit is 120 by default
		if ((int) $this->aSettings['teaser_character_limit'] == 0) {
			$this->aSettings['teaser_character_limit'] = 120;
		}

		//teaser cont is 6 by default
		if ((int) $this->aSettings['teaser_count'] == 0) {
			$this->aSettings['teaser_count'] = 6;
		}

		//teasersort is creationdate by default
		if (strlen($this->aSettings['teaser_sort']) == 0) {
			$this->aSettings['teaser_sort'] = 'creationdate';
		}

		//teaser style is liststyle by default
		if (strlen($this->aSettings['teaser_style']) == 0) {
			$this->aSettings['teaser_style'] = 'cms_teaser_style_blog.html';
		}

		//teaser image width default
		if ((int)$this->aSettings['teaser_image_width'] == 0) {
			$this->aSettings['teaser_image_width'] = 100;
		}

		//teaser image height default
		if ((int)$this->aSettings['teaser_image_height'] == 0) {
			$this->aSettings['teaser_image_height'] = 75;
		}

		//cms type head default
		if (strlen($this->aSettings['teaser_source_head']) == 0) {
			$this->aSettings['teaser_source_head'] = 'CMS_HTMLHEAD';
		}

		//cms type text default
		if (strlen($this->aSettings['teaser_source_text']) == 0) {
			$this->aSettings['teaser_source_text'] = 'CMS_HTML';
		}

		//cms type image default
		if (strlen($this->aSettings['teaser_source_image']) == 0) {
			$this->aSettings['teaser_source_image'] = 'CMS_IMG';
		}

		//cms type date default
		if (strlen($this->aSettings['teaser_source_date']) == 0) {
			$this->aSettings['teaser_source_date'] = 'CMS_DATE';
		}

		//sort order of teaser articles
		if (strlen($this->aSettings['teaser_sort_order']) == 0) {
			$this->aSettings['teaser_sort_order'] = 'asc';
		}

        //teaser image crop option
        if (strlen($this->aSettings['teaser_image_crop']) == 0 || $this->aSettings['teaser_image_crop'] == 'false') {
            $this->aSettings['teaser_image_crop'] = 'false';
        }
	}

	/**
	 * Function gets path to an image of base of idupload in contenido,
	 * scales this image on basis of teaser settings and returns path to
	 * scaled image. It is also possible to give path to image directly,
	 * in this case set fourth parameter to true
	 *
	 * @param integer $iImage - idupl of image to use for teaser
	 * @param integer $iMaxX - maximum image width
	 * @param integer $iMaxY - maximum image height
	 * @param boolean $bIsFile - in case of a direct file path retrival from database is not needed
	 * @return string - <img> tag contains scaled image
	 *
	 * @access private
	 */
	private function getImage($iImage, $iMaxX, $iMaxY, $bCropped, $bIsFile = false) {
		$sContent = '';

        if ($bCropped == 'true') {
            $bCropped = true;
        } else {
            $bCropped = false;
        }

		//check if there is a need to get image path
		if ($bIsFile == false) {
			//get path out of database
			$sSQL = 'SELECT * FROM '.$this->aCfg['tab']['upl'].' WHERE idupl = '.$iImage;

			$this->oDb->query($sSQL);
			if ($this->oDb->next_record()) {
				$sTeaserImage = $this->aCfgClient[$this->iClient]['path']['frontend'].'upload/'.$this->oDb->f("dirname").$this->oDb->f("filename");
			}
		} else {
			$sTeaserImage = $iImage;
		}

		//scale image if exists and return it
		if (file_exists($sTeaserImage)) {
		     //Scale Image using capiImgScale
			$sImgSrc = capiImgScale ($sTeaserImage, $iMaxX, $iMaxY, $bCropped);

			if ($this->sUseXHTML == 'true' ) {
				$sLetter = ' /';
			} else {
				$sLetter = '';
			}

			//Put Image into the teasertext
            $sContent = '<img alt="" src="'.$sImgSrc.'" class="teaser_image"'.$sLetter.'>'.$sContent;
        }

		return $sContent;
	}

	/**
	 * Function retrives name of an article by its id from database
	 *
	 * @param integer $iIdArt - Contenido article id
	 * @return string - name of article
	 *
	 * @access private
	 */
	private function getArtName($iIdArt) {
		$iIdArt = (int) $iIdArt;

		//get article name from database
		$sSql = 'SELECT * FROM '.$this->aCfg['tab']['art_lang'].' WHERE idart = '.$iIdArt;
		$this->oDb->query($sSql);
        if ($this->oDb->next_record()) {
			return $this->oDb->f('title');
		} else {
			return 'Unknown Article';
		}
	}

	/**
	 * Teaser allows to get a list of ids in which article content is searched in
	 * article like 1,2,5,6 the result with largest character count is returned
	 *
	 * @param object $oArticle - Contenido article object
	 * @param string $sIdType - Name of Content type to extract informations from
	 * @param integer $iIdType - list of ids to search in
	 * @return string - largest result of content
	 *
	 * @access private
	 */
	private function getArtContent(&$oArticle, $sIdType, $iIdType) {
		$sReturn = '';

		//split ids, if there is only one id, array has only one place filled, that is also ok
		$aIds = explode(',', $iIdType);
		foreach ($aIds as $iCurIdType) {
			$sTmp = $oArticle->getContent($sIdType, $iCurIdType);
			//check for largest result and replace when new value is larger
			if (strlen($sReturn) < strlen($sTmp)) {
				$sReturn = $sTmp;
			}
		}

		return $sReturn;
	}

	/**
	 * When a HTML Code is given for a Teaser image try to find a image in this code and generate
	 * Teaser image on that basis
	 *
	 * @param string $sContent - HTML string to search image in
	 * @return img tag containing scaled image
	 *
	 * @access private
	 */
	private function extractImage($sContent) {
		$sImage = '';

		//search an image tag
		$sRegEx = "/<img[^>]*?>.*?/i";

		$aMatch = array ();
		preg_match($sRegEx, $sContent, $aMatch);

		//if found extract its src content
		$sRegEx = "/(src)(=)(['\"]?)([^\"']*)(['\"]?)/i";
		$aImg = array ();
		preg_match($sRegEx, $aMatch[0], $aImg);

		//check if this image lies in upload folder
		$iPos = strrpos($aImg[4], $this->aCfgClient[$this->iClient]["upload"]);
		if (!is_bool($iPos)) {
			//if it is generate full internal path to image and scale it for display using class internal function getImage()
			$sFile = $this->aCfgClient[$this->iClient]['path']['frontend'].$aImg[4];
			$sImage = $this->getImage($sFile, $this->aSettings['teaser_image_width'], $this->aSettings['teaser_image_height'], $this->aSettings['teaser_image_crop'], true);
		}

		return $sImage;
	}

	/**
	 * In edit and view mode this function fills teaser template with informations from an
	 * Contenido article object
	 *
	 * @param object $oArticle - Contenido Article object
	 * @param object $oTpl - Contenido Template object (as reference)
	 * @return boolean - success state of this operation
	 *
	 * @access private
	 */
	private function fillTeaserTemplateEntry($oArticle, &$oTpl) {
		global $cCurrentModule;

		//get necessary informations for teaser from articles use properties in a Settings for retirval
		$sTitle = $this->getArtContent($oArticle, $this->aSettings['teaser_source_head'], $this->aSettings['teaser_source_head_count']);
		$sText = $this->getArtContent($oArticle, $this->aSettings['teaser_source_text'], $this->aSettings['teaser_source_text_count']);
		$iImage = $this->getArtContent($oArticle, $this->aSettings['teaser_source_image'], $this->aSettings['teaser_source_image_count']);
		$sDate = $this->getArtContent($oArticle, $this->aSettings['teaser_source_date'], $this->aSettings['teaser_source_date_count']);
		$iIdArt = $oArticle->getField('idart');
		$iPublished = $oArticle->getField('published');
		$iOnline = $oArticle->getField('online');

		if ( $iOnline == 1 ) {
			//teaserfilter defines strings which must be contained in text for display.
			//if string is defined check if article contains this string and abort, if article does not contain this string
			if ($this->aSettings['teaser_filter'] != '') {
				$iPosText = strrpos(conHtmlEntityDecode($sText), $this->aSettings['teaser_filter']);
				$iPosHead = strrpos(conHtmlEntityDecode($sTitle), $this->aSettings['teaser_filter']);
				if (is_bool($iPosText) && !$iPosText && is_bool($iPosHead) && !$iPosHead) {
					return false;
				}
			}

			//convert date to readable format
			if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $iPublished, $aResults)) {
				$iPublished = $aResults[3].'.'.$aResults[2].'.'.$aResults[1];
			}

			//strip tags in teaser text and cut it if it is to long
			$sTitle = trim(strip_tags($sTitle));
			$sText = trim(strip_tags($sText));
			if (strlen($sText) >  $this->aSettings['teaser_character_limit']) {
				$sText = capiStrTrimAfterWord($sText, $this->aSettings['teaser_character_limit']).'...';
			}

			//try to get a teaser image directly from cms_img or try to extract if a content type is given, wich contains html
			if ((int) $iImage > 0) {
				$sImage = $this->getImage($iImage, $this->aSettings['teaser_image_width'], $this->aSettings['teaser_image_height'], $this->aSettings['teaser_image_crop']);
				$oTpl->set('d', 'IMAGE', $sImage);
			} else if (strip_tags($iImage) != $iImage && strlen($iImage) > 0) {
				$sImage = $this->extractImage($iImage);
				if (strlen($sImage) > 0) {
					$oTpl->set('d', 'IMAGE', $sImage);
				} else {
					$oTpl->set('d', 'IMAGE', '');
				}
			}else{
				$oTpl->set('d', 'IMAGE', '');
			}

			// strip all tags from manual teaser date
			$sDate = strip_tags($sDate);

			//set generated values to teaser template
			$oTpl->set('d', 'TITLE', $sTitle);
			$oTpl->set('d', 'TEXT', $sText);

			$oTpl->set('d', 'IDART', $iIdArt);
			$oTpl->set('d', 'ART_URL', 'front_content.php?idart='.$iIdArt);
			$oTpl->set('d', 'PUBLISHED', $iPublished);
			$oTpl->set('d', 'PUBLISHED_MANUAL', $sDate);

			if ( $sDate != "" ) {
				$oTpl->set('d', 'PUBLISHED_COMBINED', $sDate);
			} else {
				$oTpl->set('d', 'PUBLISHED_COMBINED', $iPublished);
			}

			foreach( self::$aTranslations as $sKey => $sValue ) {
				$oTpl->set('d', $sKey, mi18n( $sValue ));
			}

			$oTpl->next();
		}

		return true;
	}

	/**
	 * Function is called in edit- and viewmode in order to generate teasercode for output
	 *
	 * @param boolean $bEditmode - in editmode skip encoding because it is done in getAllWidgetEdit()
	 * @return html string of select box
	 *
	 * @access public
	 */
	public function getAllWidgetOutput($bEditmode = false) {
		$oTpl = new Template();
		//set title of teaser
		$oTpl->set('s', 'TITLE', $this->aSettings['teaser_title']);

		//decide if it is a manual or category teaser
		if ($this->aSettings['teaser_manual'] == 'true' && count($this->aSettings['teaser_manual_art']) > 0) {
			$i = 0;
			//in manual case get all art to display and generate article objects manually
			foreach ($this->aSettings['teaser_manual_art'] as $iIdArt) {
				$oArticle = new Article($iIdArt, $this->iClient, $this->iLang);

				//try to fill teaser image
				if ($this->fillTeaserTemplateEntry($oArticle, $oTpl)) {
					$i++;

					//break render, if teaser limit is reached
					if ($i == $this->aSettings['teaser_count'])
						break;
				}
			}
		} else {
			//in case of autmatic teaser use class Contenido_Category_Articles for getting all arts in category
			$oConCatArt = new Contenido_Category_Articles($this->oDb, $this->aCfg, $this->iClient, $this->iLang);
			//decide to teaser articles or not
			if ($this->aSettings['teaser_start'] == 'true') {
				$aArticles = $oConCatArt->getArticlesInCategory($this->aSettings['teaser_category'], $this->aSettings['teaser_sort'], $this->aSettings['teaser_sort_order']);
			} else {
				$aArticles = $oConCatArt->getNonStartArticlesInCategory($this->aSettings['teaser_category'], $this->aSettings['teaser_sort'], $this->aSettings['teaser_sort_order']);
			}

			$i = 0;
			//iterate over all found articles
			foreach ($aArticles as $oArticle) {
				//try to fill teaser image
				if ($this->fillTeaserTemplateEntry($oArticle, $oTpl)) {
					$i++;
					//break render, if teaser limit is reached
					if ($i == $this->aSettings['teaser_count'])
						break;
				}
			}
		}

        $sCode = '';
		//generate teasertemplate
        if (file_exists($this->aCfgClient[$this->iClient]['path']['frontend'].'templates/'.$this->aSettings['teaser_style'])) {
            $sCode = $oTpl->generate($this->aCfgClient[$this->iClient]['path']['frontend'].'templates/'.$this->aSettings['teaser_style'], 1);
        }

		return $sCode;
		//use this to show xml document which contains teaser settings
		#return '<pre>'.conHtmlentities($this->sContent).'</pre>';
	}

	/**
	 * Dynamic filelist generator.
	 * This method is executed every time the filelist is displayed.
	 *
	 * @return	string	output of the filelist
	 */
	public function getAllWidgetView() {
		$sCode = '\";?><?php
					$oTeaser = new Cms_Teaser(\'%s\', %s, 0, "", $cfg, null, "", $client, $lang, $cfgClient, null);

					echo $oTeaser->getAllWidgetOutput();
				 ?><?php echo \"';

		$sCode = sprintf($sCode, $this->sContent, $this->iId);
		return $sCode;
	}
}

?>