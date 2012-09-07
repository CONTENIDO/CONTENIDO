<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Various derived HTML class elements especially useful          
 * in the input area of modules                                   
 * Simple table generation class especially useful to generate     
 * backend configuration table. May be used also in Frontend,       
 * but note the globally used variables ($cfg)  
 * 
 * Usage: Store file in client/includes folder (generate the       
 * includes folder, if not available). Include the file     
 * in your modules using                                    
 * cInclude("frontend", "includes/class.input.helper.php"); 
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    2.1 (formerly known as functions.input.helper.php)
 * @author     Björn Behrens (HerrB), http://www.btech.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2007-06-18
 *   modified 2008-07-03, bilal arslan, added security fix
 *
 *   $Id: class.input.helper.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}

// Including main HTML class
cInclude("classes", "class.htmlelements.php");

// Select box with additional functions for category and article selection
class cHTMLInputSelectElement extends cHTMLSelectElement
{
	/**
	 * Constructor. Creates an HTML select field (aka "DropDown").
	 *
	 * @param string 	$sName		Name of the element
	 * @param int		$iWidth		Width of the select element
	 * @param string	$sID		ID of the element
	 * @param string	$bDisabled	Item disabled flag (non-empty to set disabled)
	 * @param int		$iTabIndex	Tab index for form elements
	 * @param string	$sAccesskey	Key to access the field
	 *
	 * @return none
	 **/
	function cHTMLInputSelectElement ($sName, $iWidth = "", $sID = "", $bDisabled = false, $iTabIndex = null, $sAccessKey = "")
	{
		cHTMLSelectElement :: cHTMLSelectElement($sName, $iWidth, $sID, $bDisabled, $iTabIndex, $sAccessKey);
	}


	/**
	 * Function addArticles. Adds articles to select box values.
	 *
	 * @param int		$iIDCat		idcat of the category to be listed
	 * @param bool		$bColored	Add color information to option elements
	 * @param bool		$bArtOnline	If true, only online articles will be added
	 * @param string	$sSpaces	Just some "&nbsp;" to show data hierarchically (used in conjunction with addCategories)
	 *
	 * @return int 		Number of items added
	 **/
	function addArticles ($iIDCat, $bColored = false, $bArtOnline = true, $sSpaces = "")
	{
		global $cfg, $lang;

		$oDB = new DB_Contenido;

		if (is_numeric($iIDCat) && $iIDCat > 0)
		{
			$sSQL  = "SELECT tblArtLang.title AS title, tblArtLang.idartlang AS idartlang, tblCatArt.idcat AS idcat, ";
			$sSQL .= "tblCatArt.idcatart AS idcatart, tblCatArt.is_start AS isstart, tblArtLang.online AS online, ";
			$sSQL .= "tblCatLang.startidartlang as idstartartlang ";
			$sSQL .= "FROM ".$cfg["tab"]["art_lang"]." AS tblArtLang, ".$cfg["tab"]["cat_art"]." AS tblCatArt, ";
			$sSQL .= $cfg["tab"]["cat_lang"]." AS tblCatLang ";
			$sSQL .= "WHERE tblCatArt.idcat = '".Contenido_Security::toInteger($iIDCat)."' AND tblCatLang.idcat = tblCatArt.idcat AND tblCatLang.idlang = tblArtLang.idlang AND ";

			if ($bArtOnline) {
				$sSQL .= "tblArtLang.online = '1' AND ";
			}

			$sSQL .= "tblArtLang.idart = tblCatArt.idart AND tblArtLang.idlang = '".Contenido_Security::escapeDB($lang, $oDB)."' ";
			if ($cfg["is_start_compatible"] == true) {
				$sSQL .= "ORDER BY tblCatArt.is_start DESC, tblArtLang.title"; // Getting start article as first article
			} else {
				$sSQL .= "ORDER BY tblArtLang.title";
			}

			$oDB->query($sSQL);

			$iCount = $oDB->num_rows();
			if ($iCount == 0) {
				return 0;
			} else {
				$iCounter = count($this->_options);
				while ($oDB->next_record())
				{
					// Generate new option element
					$oOption = new cHTMLOptionElement($sSpaces."&nbsp;&nbsp;&nbsp;".substr(urldecode($oDB->f("title")), 0, 32), $oDB->f("idcatart"));

					if ($bColored)
					{
						$bIsStartArticle = false;
						if ($cfg["is_start_compatible"] == true && $oDB->f("isstart") == 1) {
							// Compatible mode and "start article" flag is set
							$bIsStartArticle = true;
						} else if ($cfg["is_start_compatible"] != true && $oDB->f("idstartartlang") == $oDB->f("idartlang")) {
							// No compatible mode and current article is start article (idstartartlang is the same for all records within a category)
							$bIsStartArticle = true;
						}

						if ($bIsStartArticle)
						{
							if ($oDB->f("online") == 0) {
								// Start article, but offline -> red
								$oOption->setStyle("color: #ff0000;");
							} else {
								// Start article -> blue
								$oOption->setStyle("color: #0000ff;");
							}
						} else if ($oDB->f("online") == 0) {
							// Offline article -> grey
							$oOption->setStyle("color: #666666;");
						}	
					}

					// Add option element to the list
					$this->addOptionElement($iCounter, $oOption);
					$iCounter++;
				}
				return $iCount;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Function addCategories. Adds category elements (optionally including articles) to select box values.
	 * Note: Using "with articles" adds the articles also - but the categories will get a negative value!
	 *       There is no way to distinguish between a category id and an article id...
	 *
	 * @param int		$iMaxLevel	Max. level shown (to be exact: except this level)
	 * @param bool		$bColored	Add color information to option elements
	 * @param bool		$bCatVisible	If true, only add idcat as value, if cat is visible
	 * @param bool		$bCatPublic	If true, only add idcat as value, if cat is public
	 * @param bool		$bWithArt	Add also articles per category
	 * @param bool		$bArtOnline	If true, show only online articles
	 *
	 * @return int		Number of items added
	 **/
	function addCategories ($iMaxLevel = 0, $bColored = false, $bCatVisible = true, $bCatPublic = true, 
				$bWithArt = false, $bArtOnline = true)
	{
		global $cfg, $client, $lang;

		$oDB   = new DB_Contenido;

		$sSQL  = "SELECT tblCat.idcat AS idcat, tblCatLang.name AS name, ";
		$sSQL .= "tblCatLang.visible AS visible, tblCatLang.public AS public, tblCatTree.level AS level ";
		$sSQL .= "FROM ".$cfg["tab"]["cat"]." AS tblCat, ".$cfg["tab"]["cat_lang"]." AS tblCatLang, ";
		$sSQL .= $cfg["tab"]["cat_tree"]." AS tblCatTree ";
		$sSQL .= "WHERE tblCat.idclient = '".Contenido_Security::escapeDB($client, $oDB)."' AND tblCatLang.idlang = '".Contenido_Security::escapeDB($lang, $oDB)."' AND ";
		$sSQL .= "tblCatLang.idcat = tblCat.idcat AND tblCatTree.idcat = tblCat.idcat ";

		if ($iMaxLevel > 0) {
			$sSQL .= "AND tblCatTree.level < '".Contenido_Security::escapeDB($iMaxLevel, $oDB)."' ";
		}
		$sSQL .= "ORDER BY tblCatTree.idtree";

		$oDB->query($sSQL);

		$iCount = $oDB->num_rows();
   		if ($iCount == 0) {
			return false;
		} else {
			$iCounter = count($this->_options);
			while ($oDB->next_record())
			{
				$sSpaces = "";
				$sStyle  = "";
				$iID     = $oDB->f("idcat");

				for ($i = 0; $i < $oDB->f("level"); $i++) {
					$sSpaces .= "&nbsp;&nbsp;&nbsp;";
				}

				// Generate new option element
				if (($bCatVisible && $oDB->f("visible") == 0) || 
				    ($bCatPublic && $oDB->f("public") == 0)) {
					// If category has to be visible or public and it isn't, don't add value
					$sValue = "";
				} else if ($bWithArt) {
					// If article will be added, set negative idcat as value
					$sValue = "-".$iID;
				} else {
					// Show only categories - and everything is fine...
					$sValue = $iID;
				}
				$oOption = new cHTMLOptionElement($sSpaces.">&nbsp;".urldecode($oDB->f("name")), $sValue);

				// Coloring option element, restricted shows grey color
				$oOption->setStyle("background-color: #EFEFEF");
				if ($bColored && ($oDB->f("visible") == 0 || $oDB->f("public") == 0)) {
					$oOption->setStyle("color: #666666;");
				}

				// Add option element to the list
				$this->addOptionElement($iCounter, $oOption);

				if ($bWithArt) {
					$iArticles = $this->addArticles($iID, $bColored, $bArtOnline, $sSpaces);
					$iCount   += $iArticles;
				}
				$iCounter = count($this->_options);
			}
		}
		return $iCount;
	}

	/**
	 * Function addTypesFromArt. Adds types and type ids which are available for the specified article
	 *
	 * @param int		$iIDCatArt	Article id
	 * @param string	$sTypeRange	Komma separated list of Contenido type ids which may be in the resulting list (e.g. '1','17','28')
	 *
	 * @return int		Number of items added
	 **/
	function addTypesFromArt ($iIDCatArt, $sTypeRange = "")
	{
		global $cfg, $lang;

		$oDB = new DB_Contenido;

		if (is_numeric($iIDCatArt) && $iIDCatArt > 0)
		{
			$sSQL  = "SELECT tblContent.typeid AS typeid, tblContent.idtype AS idtype, tblType.type AS type, tblType.description AS description, ";
			$sSQL .= "tblContent.value AS value ";
			$sSQL .= "FROM ".$cfg["tab"]["content"]." AS tblContent, ".$cfg["tab"]["art_lang"]." AS tblArtLang, ";
			$sSQL .= $cfg["tab"]["cat_art"]." AS tblCatArt, ".$cfg["tab"]["type"]." AS tblType ";
			$sSQL .= "WHERE tblContent.idtype = tblType.idtype AND tblContent.idartlang = tblArtLang.idartlang AND ";
			$sSQL .= "tblArtLang.idart = tblCatArt.idart AND tblArtLang.idlang = '". Contenido_Security::escapeDB($lang, $oDB)."' AND tblCatArt.idcatart = '". Contenido_Security::toInteger($iIDCatArt)."' ";

			if ($sTypeRange != "") {
				$sSQL .= "AND tblContent.idtype IN (". Contenido_Security::escapeDB($sTypeRange, $oDB).") ";
			}

			$sql .= "ORDER BY tblContent.idtype, tblContent.typeid";

			$oDB->query($sSQL);

			$iCount = $oDB->num_rows();
			if ($iCount == 0) {
				return false;
			} else {
				while ($oDB->next_record())
				{
					$sTypeIdentifier = "tblData.idtype = '".$oDB->f('idtype')."' AND tblData.typeid = '".$oDB->f('typeid')."'";

					// Generate new option element
					$oOption = new cHTMLOptionElement($oDB->f('type')."[".$oDB->f('typeid')."]: ".substr(strip_tags(urldecode($oDB->f("value"))), 0, 50), $sTypeIdentifier);

					// Add option element to the list
					$this->addOptionElement($sTypeIdentifier, $oOption);
				}
				return $iCount;
			}
		} else {
			return false;
		}
	}

	/**
	 * Selects specified elements as selected
	 *
	 * @param array		$aElements Array with "values" of the cHTMLOptionElement to set
	 *
	 * @return none
	 */
	function setSelected($aElements)
	{
		if (is_array($this->_options) && is_array($aElements))
		{
			foreach ($this->_options as $sKey => $oOption)
			{
				if (in_array($oOption->getAttribute("value"), $aElements))
				{
					$oOption->setSelected(true);
					$this->_options[$sKey] = $oOption;
				} else {
					$oOption->setSelected(false);
					$this->_options[$sKey] = $oOption;
				}
			}
		}
	}
}

class UI_Config_Table
{
	var $_sTplCellCode;
	var $_sTplTableFile;
	
	var $_sWidth;
 	var $_sBorder;
 	var $_sBorderColor;
 	var $_bSolidBorder;
 	var $_sPadding;
	var $_aCells;
	var $_aCellAlignment;
	var $_aCellVAlignment;
	var $_aCellColSpan;
	var $_aCellClass;
	var $_aRowBgColor;
	var $_aRowExtra;
	var $_bAddMultiSelJS;
	
	var $_sColorLight;
	var $_sColorDark;

	function UI_Config_Table()
	{
		global $cfg;

		$this->_sPadding		= 2;
		$this->_sBorder			= 0;
		$this->_sBorderColor	= $cfg['color']['table_border'];
		$this->_sTplCellCode	= '        <td align="{ALIGN}" valign="{VALIGN}" class="{CLASS}" colspan="{COLSPAN}" style="{EXTRA}white-space:nowrap;" nowrap="nowrap">{CONTENT}</td>'."\n";
		$this->_sTplTableFile	= $cfg['path']['contenido'].$cfg['path']['templates'].$cfg['templates']['generic_list'];
		$this->_sColorLight		= $cfg['color']['table_light'];
		$this->_sColorDark		= $cfg['color']['table_dark'];
	}

	function setCellTemplate($sCode)
	{
		$this->_sTplCellCode = $sCode;
	}


	function setTableTemplateFile($sPath)
	{
		$this->_sTplTableFile  = $sPath;
	}
	
	function setLightColor($sColor)
	{
		$this->_sColorLight  = $sColor;
	}
	
	function setDarkColor($sColor)
	{
		$this->_sColorDark  = $sColor;
	}

	function setAddMultiSelJS($bEnabled = true)
	{
		$this->_bAddMultiSelJS = (bool)$bEnabled;
	}

	function setWidth ($sWidth)
	{
		$this->_sWidth = $sWidth;
	}
	
	function setPadding ($sPadding)
	{
		$this->_sPadding = $sPadding;
	}

	function setBorder ($sBorder)
	{
		$this->_sBorder = $sBorder;
	}

	function setBorderColor ($sBorderColor)
	{
		$this->_sBorderColor = $sBorderColor;
	}
	
	function setSolidBorder ($bSolidBorder = true)
	{
		$this->_bSolidBorder = (bool)$bSolidBorder;
	}

	function setCell ($sRow, $sCell, $sContent)
	{
		$this->_aCells[$sRow][$sCell] = $sContent;
		$this->_aCellAlignment[$sRow][$sCell] = "";
	}

	function setCellAlignment ($sRow, $sCell, $sAlignment)
	{
		$this->_aCellAlignment[$sRow][$sCell] = $sAlignment;
	}

	function setCellVAlignment ($sRow, $sCell, $sAlignment)
	{
		$this->_aCellVAlignment[$sRow][$sCell] = $sAlignment;
	}

	function setCellColspan ($sRow, $sCell, $iColSpan)
	{
		$this->_aCellColSpan[$sRow][$sCell] = $iColSpan;
	}

	function setCellClass ($sRow, $sCell, $sClass)
	{
		$this->_aCellClass[$sRow][$sCell] = $sClass;
	}

	function setRowBgColor ($sRow, $sColor)
	{
		$this->_aRowBgColor[$sRow] = $sColor;
	}
	
	function setRowExtra ($sRow, $sExtra)
	{
		$this->_aRowExtra[$sRow] = $sExtra;
	}

	function _addMultiSelJS()
	{
		// Trick: To save multiple selections in <select>-Element, add some JS which saves the
		// selection, comma separated in a hidden input field on change.

		// Try ... catch prevents error messages, if function is added more than once
		// if (!fncUpdateSel) in JS has not worked...

		$sSkript = '              <script type="text/javascript"><!--'."\n".
			   '                 try {'."\n".
			   '                    function fncUpdateSel(sSelectBox, sStorage)'."\n".
			   '                    {'."\n".
			   '                       var sSelection = "";'."\n".
			   '                       var oSelectBox = document.getElementsByName(sSelectBox)[0];'."\n".
			   '                       var oStorage   = document.getElementsByName(sStorage)[0];'."\n".
			   '                       '."\n".
			   '                       if (oSelectBox && oStorage)'."\n".
			   '                       {'."\n".
			   '                          for (i = 0; i < oSelectBox.length; i++)'."\n".
			   '                          {'."\n".
			   '                             if(oSelectBox.options[i].selected == true)'."\n".
			   '                             {'."\n".
			   '                                if (sSelection != "")'."\n".
			   '                                   sSelection = sSelection + ",";'."\n".
			   '                                sSelection = sSelection + oSelectBox.options[i].value;'."\n".
			   '                             }'."\n".
			   '                          }'."\n".
			   '                          oStorage.value = sSelection;'."\n".
			   '                       }'."\n".
			   '                    }'."\n".
			   '                 } catch (e) { }'."\n".
			   '              //--></script>'."\n";

		return $sSkript;
	}
	
	function render($bPrint = false)
	{
		$oTable = new Template;
		$oTable->reset();

		$oTable->set('s', 'CELLPADDING', $this->_sPadding);
		$oTable->set('s', 'BORDER',      $this->_sBorder);
		$oTable->set('s', 'BORDERCOLOR', $this->_sBorderColor);

		$iColCount			= 0;
		$bDark				= false;
		$sBgColor			= "";
		$bMultiSelJSAdded	= false;
		if (is_array($this->_aCells))
		{
			foreach ($this->_aCells as $sRow => $aCells)
			{
				$iColCount++;
				//$bDark  = !$bDark;
				$sLine  = "";
				$iCount = 0;
				
				foreach ($aCells as $sCell => $sData)
				{
					$iCount++;
					$sTplCell = $this->_sTplCellCode;

					if ($this->_bSolidBorder)
					{
						if ($iCount < count($aCells))
						{
							if ($iColCount < count($this->_aCells)) {
								$sTplCell = str_replace('{EXTRA}', 'border: 0px; border-right: 1px; border-bottom: 1px; border-color: '.$this->_sBorderColor.'; border-style: solid;', $sTplCell);
							} else {
								$sTplCell = str_replace('{EXTRA}', 'border: 0px; border-right: 1px; border-color: '.$this->_sBorderColor.'; border-style: solid;', $sTplCell);
							}
						} else if ($iColCount < count($this->_aCells)) {
							$sTplCell = str_replace('{EXTRA}', 'border: 0px; border-bottom: 1px; border-color: '.$this->_sBorderColor.'; border-style: solid;', $sTplCell);
						} else {
							$sTplCell = str_replace('{EXTRA}', '', $sTplCell);
						}
					}
            	
					if ($this->_aCellAlignment[$sRow][$sCell] != "") {
						$sTplCell = str_replace('{ALIGN}', $this->_aCellAlignment[$sRow][$sCell], $sTplCell);
					} else {
						$sTplCell = str_replace('{ALIGN}', 'left', $sTplCell);
					}
            		
					if ($this->_aCellVAlignment[$sRow][$sCell] != "") {
						$sTplCell = str_replace('{VALIGN}', $this->_aCellVAlignment[$sRow][$sCell], $sTplCell);
					} else {
						$sTplCell = str_replace('{VALIGN}', 'top', $sTplCell);
					}

					if ($this->_aCellColSpan[$sRow][$sCell] != "") {
						$sTplCell = str_replace('{COLSPAN}', $this->_aCellColSpan[$sRow][$sCell], $sTplCell);
					} else {
						$sTplCell = str_replace('{COLSPAN}', '1', $sTplCell);
					}

					if ($this->_aCellClass[$sRow][$sCell] != "") {
						$sTplCell = str_replace('{CLASS}', $this->_aCellClass[$sRow][$sCell], $sTplCell);
					} else {
						$sTplCell = str_replace('{CLASS}', 'text', $sTplCell);
					}

					// Multi selection javascript
					if ($this->_bAddMultiSelJS) {
						$sData = $this->_addMultiSelJS() . $sData;
						$this->_bAddMultiSelJS = false;
					}

					$sTplCell  = str_replace('{CONTENT}', $sData, $sTplCell);
					$sLine    .= $sTplCell;
				}
				
				// Row   	
				$oTable->set('d', 'ROWS', $sLine);
				
				if ($this->_aRowBgColor[$sRow] != "") {
					$sBgColor = $this->_aRowBgColor[$sRow];
				} else if ($sBgColor == $this->_sColorLight) {
					$sBgColor = $this->_sColorDark;
				} else {
					$sBgColor = $this->_sColorLight;
				}
				$oTable->set('d', 'BGCOLOR', $sBgColor);
				
				if ($this->_aRowExtra[$sRow] != "") {
					$oTable->set('d', 'EXTRA', $this->_aRowExtra[$sRow]);
				} else {
					$oTable->set('d', 'EXTRA', '');
				}
				
				$oTable->next();
    		}
		}
			
		if ($this->_sWidth) {
			// Table: Width
			$oTable->set('s', 'EXTRA', 'width: '.$this->_sWidth.';');
		} else {
			$oTable->set('s', 'EXTRA', '');
		}
		$sRendered = $oTable->generate($this->_sTplTableFile, true, false);
						
		if ($bPrint == true) {
			echo $sRendered;	
		} else {
			return $sRendered;
		} 
	}
}
?>