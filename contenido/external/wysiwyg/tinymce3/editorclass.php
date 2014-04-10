<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * The object cTinyMCEEditor is a wrapper class to the TinyMCE WYSIWYG Editor.
 * Attributes can be defined to generate JavaScript options and functions to initialise the global
 * tinyMCE object in file ./contenido/external/wysiwyg/tinymce2/tinymce.tpl.html.
 *
 * All settings accepted by tinyMCE and its plugins may be specified using system, client
 * group or user property/setting.
 *
 * The following parameters will be always set on initialization (even, if they have been specified
 * as property. They can be set using setSetting later on, if needed):
 * document_base_url
 * cleanup_callback (-> XHTML)
 * file_browser_callback
 * external_link_list_url
 * external_image_list_url
 * flash_external_list_url
 *
 * Requirements:
 * @con_php_req 5
 * @con_template
 * /docs/techref/backend/backend.customizing.html
 * @con_notice
 * The following settings are only used in Contenido:
 * contenido_toolbar_mode: full, simple, mini, custom
 * contenido_lists: link,image,flash
 * contenido_height_html
 * contenido_height_head
 * See backend.customizing.html for details
 *
 * @package    Contenido Backend <Area>
 * @version    1.2.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  <date>
 *   modified 2007-06-13 Bj�rn Behrens/Stefan??? Using setting by type to use all tinyMCE settings (even, if they are not covered here)
 *
 *   modified 2006-10-24 Willi Man, added new tinyMCE attribute 'article_url_suffix'.
 *   It will be used in plugin advlink ./contenido/external/wysiwyg/tinymce2/jscripts/tiny_mce/plugins/advlink/jscripts/functions.js
 *   to build the anchorlist.
 *
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   modified 2008-07-21, Ingo van Peeren, fixed path for property 'content_css' default value
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *   modified 2010-09-03, Murat Purc, fixed invalid inline editor option, see [#CON-345]
 *   modified 2011-07-18, Ortwin Pinke, fixed missing idart with anchors, see [CON-406]
 *
 *   $Id: editorclass.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

cInclude("includes", "functions.lang.php");

class cTinyMCEEditor extends cWYSIWYGEditor
{
	/** Stores base url of page
	 *  @access private
	 */
	var $_sBaseURL;

	/** Stores, if GZIP compression will be used
	 * @access private
	 */
	var $_bUseGZIP = false;

	function cTinyMCEEditor ($sEditorName, $sEditorContent)
	{
		global $belang, $cfg, $cfgClient, $client, $lang, $idart;

		cWYSIWYGEditor::cWYSIWYGEditor($sEditorName, $sEditorContent);
		$this->_setEditor("tinymce3");

		// Retrieve all settings for tinymce
		$this->_aSettings = getEffectiveSettingsByType("tinymce");

		// For compatibility, read settings in previous syntax also (< V4.7, type "wysiwyg" vs. "tinymce")
		$this->_aSettings = array_merge(getEffectiveSettingsByType("wysiwyg"), $this->_aSettings);

		$this->setSetting("article_url_suffix", 'front_content.php?idart='.$idart, true); # modified 23.10.2006

		// Default values
		$this->setSetting("mode", "exact");
		$aPathFragments = explode('/', $cfgClient[$client]["path"]["htmlpath"]);
		$this->setSetting("content_css", $cfgClient[$client]["path"]["htmlpath"]."css/style_tiny.css");

		$this->setSetting("theme", "advanced");
		$this->setSetting("theme_advanced_toolbar_location", "top");
		$this->setSetting("theme_advanced_path_location", "bottom");
		$this->setSetting("remove_script_host", false);
		$this->setSetting("file_browser_callback", "myCustomFileBrowser", true);
		//$this->setSetting("urlconverter_callback", "CustomURLConverter");
		// New in V3.x
		$this->setSetting("theme_advanced_resizing", true);
		$this->setSetting("pagebreak_separator", "<!-- my page break -->"); // needs pagebreak plugin
		// Source formatting (ugh!)
		$this->setSetting("apply_source_formatting", true);
		$this->setSetting("remove_linebreaks", false); // Remove linebreaks - GREAT idea...

		// Convert URLs and Relative URLs default
		$this->setSetting("convert_urls", true);
		$this->setSetting("relative_urls", true);

		// Editor name (a comma spearated list of instances)
		$this->setSetting("elements", $sEditorName);

		// Editor language
		$aLangs = i18nGetAvailableLanguages();
		$this->setSetting("language", $aLangs[$belang][4]);
		unset ($aLangs);

		// Set document base URL
		//$this->setSetting("document_base_url", $cfgClient[$client]["path"]["htmlpath"], true);

		// The following "base URL" is the URL used to reference JS script files
		// - it is not the base href value
		$this->setBaseURL(preg_replace('/^https?:\/\/[^\/]+(.*)$/', '$1', $this->getEditorPath()));

		// XHTML
		if (getEffectiveSetting("generator", "xhtml", false) == "true")
		{
			$this->setXHTMLMode(true);
		} else {
			$this->setXHTMLMode(false);
		}

		// GZIP
		if ($this->_aSettings["contenido_gzip"] == "true")
		{
			$this->setGZIPMode(true);
		} else {
			$this->setGZIPMode(false);
		}

		// Stylesheet file, for compatibility
		if (!array_key_exists("content_css", $this->_aSettings) && array_key_exists("tinymce-stylesheet-file", $this->_aSettings))
		{
			$this->setSetting("content_css", $this->_aSettings["tinymce-stylesheet-file"], true);
		}

		// Set lists (for links, images and flash elements)
		$this->setLists();

		// Set user defined styles (be sure, that previous and SPAW syntax works)
		$this->setUserDefinedStyles();

		// Width and height
		$this->setSetting("width", "100%");
		$this->setSetting("height", "480px");

		// Text direction (rtl = right to left)
		$sDirection = langGetTextDirection($lang);
		$this->setSetting("directionality", $sDirection);

		if ($sDirection == "rtl")
		{
			$this->setSetting("theme_advanced_toolbar_align", "right", true);
		} else {
			$this->setSetting("theme_advanced_toolbar_align", "left", true);
		}

		// Date and time formats
		$this->setSetting("plugin_insertdate_dateFormat", $this->convertFormat(getEffectiveSetting("backend", "timeformat_date", "Y-m-d")));
		$this->setSetting("plugin_insertdate_timeFormat", $this->convertFormat(getEffectiveSetting("backend", "timeformat_time", "H:i:s")));

		// Setting the toolbar (toolbar_mode and tinymce-toolbar-mode accepted)
		$sMode = "full";
		if (array_key_exists("tinymce-toolbar-mode", $this->_aSettings))
		{
			$sMode = $this->_aSettings["tinymce-toolbar-mode"];
		}
		if (array_key_exists("contenido_toolbar_mode", $this->_aSettings))
		{
			$sMode = $this->_aSettings["contenido_toolbar_mode"];
		}
		$this->setToolbar(trim(strtolower($sMode)));

		// Valid elements, for compatibility also accepts "tinymce-valid-elements"
		if (!array_key_exists("valid_elements", $this->_aSettings) &&
			 array_key_exists("tinymce-valid-elements", $this->_aSettings))
		{
			$this->setSetting("valid_elements", $this->_aSettings["tinymce-valid-elements"], true);
		}
		$this->setSetting("valid_elements", "+a[name|href|target|title],strong/b[class],em/i[class],strike[class],u[class],p[dir|class|align],ol,ul,li,br,img[class|src|border=0|alt|title|hspace|vspace|width|height|align],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|align|style],tr[class|rowspan|width|height|align|valign|style],td[dir|class|colspan|rowspan|width|height|align|valign|style],div[dir|class|align],span[class|align],pre[class|align],address[class|align],h1[dir|class|align],h2[dir|class|align],h3[dir|class|align],h4[dir|class|align],h5[dir|class|align],h6[dir|class|align],hr");

		// Extended valid elements, for compatibility also accepts "tinymce-extended-valid-elements"
		if (!array_key_exists("extended_valid_elements", $this->_aSettings) &&
			 array_key_exists("tinymce-extended-valid-elements", $this->_aSettings))
		{
			$this->setSetting("extended_valid_elements", $this->_aSettings["tinymce-extended-valid-elements"]);
		}
		$this->setSetting("extended_valid_elements", "form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]");

		// Background color
		if (!array_key_exists("contenido_background_color", $this->_aSettings))
		{
			if (array_key_exists("tinymce-backgroundcolor", $this->_aSettings))
			{
				$this->setSetting("contenido_background_color", $this->_aSettings["tinymce-backgroundcolor"], true);
			} else {
				$this->setSetting("contenido_background_color", "white", true);
			}
		}

		// Clean all possible URLs
		$this->cleanURLs();

		// Remove Contenido specific settings
		// contenido_background_color is used in getEditor
		unset(
			$this->_aSettings["contenido_toolbar_mode"],
			$this->_aSettings["contenido_lists"]
		);
		// Remove obsolete, deprecated values
		unset(
			$this->_aSettings["tinymce-stylesheet-file"],
			$this->_aSettings["tinymce-valid-elements"],
			$this->_aSettings["tinymce-extended-valid-elements"],
			$this->_aSettings["tinymce-lists"],
			$this->_aSettings["tinymce-styles"],
			$this->_aSettings["tinymce-toolbar-mode"],
			$this->_aSettings["tinymce-toolbar1"],
			$this->_aSettings["tinymce-toolbar2"],
			$this->_aSettings["tinymce-toolbar3"],
			$this->_aSettings["tinymce-plugins"]
		);
	}

	function convertFormat ($sInput)
	{
		$aFormatCodes = array(
			"y" => "%y",
			"Y" => "%Y",
			"d" => "%d",
			"m" => "%m",
			"H" => "%H",
			"h" => "%I",
			"i" => "%M",
			"s" => "%S",
			"a" => "%P",
			"A" => "%P"
		);

		foreach ($aFormatCodes as $sFormatCode => $sReplacement)
		{
			$sInput = str_replace($sFormatCode, $sReplacement, $sInput);
		}

		return ($sInput);
	}

	function setUserDefinedStyles()
	{
		$sStyles = "";

		if(array_key_exists("theme_advanced_styles", $this->_aSettings))
		{
			$sStyles = $this->_aSettings["theme_advanced_styles"];
		} else if(array_key_exists("tinymce-styles", $this->_aSettings))
		{
			$sStyles = $this->_aSettings["tinymce-styles"];
		}

		if ($sStyles)
		{
			$this->setSetting("theme_advanced_styles", preg_replace('/;$/i', '', str_replace("|","=", trim($sStyles))), true);
		}
	}

	/**
	 * The special name "contenido_lists", for compatibility also accepts "tinymce-lists"
	 * @param string	sLists	Deprecated, for compatibility, only
	 */
	function setLists($sLists = "")
	{
		global $lang, $client;

		if ($sLists == "")
		{
			if (array_key_exists("contenido_lists", $this->_aSettings))
			{
				$sLists = $this->_aSettings["contenido_lists"];
			}
			else if (array_key_exists("tinymce-lists", $this->_aSettings))
			{
				$sLists = $this->_aSettings["tinymce-lists"];
			}
		}

		$aLists = array();
		$aLists = explode(",", strtolower(str_replace(" ", "", $sLists)));

		if (in_array("link", $aLists))
		{
			$this->setSetting("external_link_list_url", $this->_sBaseURL."list.php?mode=link&lang=".$lang."&client=".$client."#", true);
		}
		if(in_array("image", $aLists))
		{
			$this->setSetting("external_image_list_url", $this->_sBaseURL."list.php?mode=image&lang=".$lang."&client=".$client."#", true);
		}
		if (in_array("flash", $aLists))
		{
			$this->setSetting("flash_external_list_url", $this->_sBaseURL."list.php?mode=flash&lang=".$lang."&client=".$client."#", true);
		}
		if (in_array("media", $aLists))
		{
			$this->setSetting("media_external_list_url", $this->_sBaseURL."list.php?mode=media&lang=".$lang."&client=".$client."#", true);
		}
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setToolbarMode ($sMode)
	{
		$this->setToolbar($sMode);
	}

	function setXHTMLMode ($bEnabled = true)
	{
		if ($bEnabled) {
			$this->setSetting("cleanup_callback", "", true);
		} else {
			$this->setSetting("cleanup_callback", "CustomCleanupContent", true);
		}
	}

	function setGZIPMode ($bEnabled = true)
	{
		if ($bEnabled) {
			$this->_bUseGZIP = true;
		} else {
			$this->_bUseGZIP = false;
		}
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setCustomToolbar ($sToolbar1, $sToolbar2, $sToolbar3, $sPlugins)
	{
		$this->setSetting("toolbar_mode", "custom", true);
		$this->setSetting("theme_advanced_buttons1", $sToolbar1, true);
		$this->setSetting("theme_advanced_buttons2", $sToolbar2, true);
		$this->setSetting("theme_advanced_buttons3", $sToolbar3, true);
		$this->setSetting("plugins", $sPlugins, true);
	}

	/**
	 * For compatibility also accepts "tinymce-toolbar-mode", "tinymce-toolbar1-3" and "tinymce-plugins"
	 */
	function setToolbar($sMode = "")
	{
		global $cfg, $cfgClient, $client;

		switch ($sMode)
		{
			case "full": // Show all options
				$this->setSetting("theme_advanced_buttons1", "cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo,|,bold,italic,underline,strikethrough,sub,sup,|,insertdate,inserttime,preview,|,styleselect,|,visualchars,nonbreaking,template,pagebreak,|,help,|,fullscreen", true);
				$this->setSetting("theme_advanced_buttons2", "link,unlink,anchor,image,media,advhr,|,bullist,numlist,|,outdent,indent,blockquote,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,visualaid,charmap,cleanup,|,code", true);
				$this->setSetting("theme_advanced_buttons3", "tablecontrols,|,formatselect,fontselect,fontsizeselect,|,styleprops,|,cite,abbr,acronym,del,ins,attribs", true);
				//safari,table,save,advhr,advimage,advlink,pagebreak,style,layer,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template
				$this->setSetting("plugins", "safari,table,save,advhr,advimage,advlink,pagebreak,style,layer,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups", true);
                $this->setSetting("theme_advanced_toolbar_align", "left", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->setSetting($sKey, $sValue, true);
                }

                break;

            case "fullscreen": // Show all options
				$this->setSetting("theme_advanced_buttons1", "cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo,|,bold,italic,underline,strikethrough,sub,sup,|,insertdate,inserttime,preview,|,styleselect,|,visualchars,nonbreaking,template,pagebreak,|,help,|,fullscreen", true);
				$this->setSetting("theme_advanced_buttons2", "link,unlink,anchor,image,media,advhr,|,bullist,numlist,|,outdent,indent,blockquote,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,visualaid,charmap,cleanup,|,code", true);
				$this->setSetting("theme_advanced_buttons3", "tablecontrols,|,formatselect,fontselect,fontsizeselect,|,styleprops,|,cite,abbr,acronym,del,ins,attribs", true);
				//safari,table,save,advhr,advimage,advlink,pagebreak,style,layer,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template
				$this->setSetting("plugins", "safari,table,save,advhr,advimage,advlink,pagebreak,style,layer,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups", true);
                $this->setSetting("theme_advanced_toolbar_align", "left", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_fullscreen");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->setSetting($sKey, $sValue, true);
                }

                break;

			case "simple": // Does not show font and table options
				$this->setSetting("theme_advanced_buttons1", "cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo,|,bold,italic,underline,strikethrough,sub,sup,|,insertdate,inserttime,preview,|,styleselect", true);
				$this->setSetting("theme_advanced_buttons2", "link,unlink,anchor,image,flash,advhr,|,bullist,numlist,|,outdent,indent,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,visualaid,charmap,cleanup,|,code", true);
				$this->setSetting("theme_advanced_buttons3", "", true);
				$this->setSetting("plugins", "advhr,advimage,advlink,insertdatetime,preview,flash,searchreplace,print,contextmenu,paste,directionality", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_simple");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->setSetting($sKey, $sValue, true);
                }

                break;

			case "mini": // Minimal toolbar
				$this->setSetting("theme_advanced_buttons1", "undo,redo,|,bold,italic,underline,strikethrough,|,link", true);
				$this->setSetting("theme_advanced_buttons2", "", true);
				$this->setSetting("theme_advanced_buttons3", "", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_mini");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->setSetting($sKey, $sValue, true);
                }

				break;

			case "custom": // Custom toolbar
				// tinymce-toolbar1/2/3 and tinymce-plugins are only mentioned for compatibility
				// They are ignored, if theme_advanced_buttons1/2/3 and plugins have been already
				// specified
				$this->setSetting("theme_advanced_buttons1", $this->_aSettings["tinymce-toolbar1"]);
				$this->setSetting("theme_advanced_buttons2", $this->_aSettings["tinymce-toolbar2"]);
				$this->setSetting("theme_advanced_buttons3", $this->_aSettings["tinymce-toolbar3"]);
				$this->setSetting("plugins", $this->_aSettings["tinymce-plugins"]);
                $this->setSetting("theme_advanced_toolbar_location", "bottom");

                $aCustSettings = getEffectiveSettingsByType("tinymce_custom");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->setSetting($sKey, $sValue, true);
                }

				break;

            case "inline_edit":
                $this->setSetting("theme_advanced_buttons1", "bold,italic,underline,strikethrough,separator,undo,separator,bullist,numlist,separator,forecolor,backcolor,separator,justifyleft,justifycenter,justifyright,separator,fullscreen,separator,save,close", true);
				$this->setSetting("theme_advanced_buttons2", "", true);
				$this->setSetting("theme_advanced_buttons3", "", true);

				$this->setSetting("setupcontent_callback", "myCustomSetupContent", true);

                $this->unsetSetting("width");

                $this->setSetting("height", "210px", true);
				$this->setSetting("plugins", "table,inlinepopups,fullscreen,-close", true);
                $this->setSetting("mode", "exact", true);
                $this->setSetting("elements", "*", true);
				$this->setSetting("content_css", $cfgClient[$client]["path"]["htmlpath"]."css/style_tiny.css", true);

				if (!array_key_exists("auto_resize", $this->_aSettings))
					$this->setSetting("auto_resize", "false", true);

				if (!array_key_exists("theme_advanced_toolbar_location", $this->_aSettings))
					$this->setSetting("theme_advanced_toolbar_location", "top", true);

				if (!array_key_exists("theme_advanced_resizing_use_cookie", $this->_aSettings))
					$this->setSetting("theme_advanced_resizing_use_cookie", "false", true);

				if (!array_key_exists("theme_advanced_toolbar_align", $this->_aSettings))
					$this->setSetting("theme_advanced_toolbar_align", "center", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_inline");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->setSetting($sKey, $sValue, true);
                }

				break;

		   default: // Default options
		      $this->setSetting("theme_advanced_buttons1", "undo,redo,|,bold,italic,underline,strikethrough,|,link,unlink,anchor,image,flash,advhr,|,tablecontrols", true);
		      $this->setSetting("theme_advanced_buttons2", "styleselect,|,bullist,numlist,|,outdent,indent,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,sub,sup,|,code", true);
		      $this->setSetting("theme_advanced_buttons3", "", true);
		      $this->setSetting("plugins", "table,advhr,advimage,advlink,flash,searchreplace,contextmenu,paste", true);

              $aCustSettings = getEffectiveSettingsByType("tinymce_default");
              foreach ($aCustSettings as $sKey => $sValue) {
                $this->setSetting($sKey, $sValue, true);
              }
		}
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setStylesheet ($sStylesheet)
	{
		$this->setSetting("content_css", $sStylesheet, true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setStyles ($sStyles)
	{
		$this->setSetting("theme_advanced_styles", $sStyles, true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setWidth ($iWidth)
	{
		$this->setSetting("width", $iWidth, true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setHeight ($iHeight)
	{
		$this->setSetting("width", $iHeight, true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setDocumentBaseURL ($sDocumentBaseURL)
	{
		$this->setSetting("document_base_url", $sDocumentBaseURL, true);
	}

	function cleanURLs()
	{
		global $sess;

		// Add the path to the following values
		$aParameters = array(
						//builtin
						'content_css',
						'popups_css',
						'popups_css_add',
						'editor_css',
						// plugins
						'plugin_preview_pageurl', //preview plugin
						'media_external_list_url', //media plugin
						'template_external_list_url' //template plugin
		);

		foreach ($aParameters as $sParameter)
		{
			if (array_key_exists($sParameter, $this->_aSettings))
			{
				$this->setSetting($sParameter, $this->addPath($this->_aSettings[$sParameter]), true);
			}
		}

		// Session for template and media support files that are written in PHP
		$aParameters = array(
					'media_external_list_url', //media plugin
					'template_external_list_url' //template plugin
		);

		foreach ($aParameters as $sParameter)
		{
			if (array_key_exists($sParameter, $this->_aSettings) &&
				preg_match('/\\.php$/i', $this->_aSettings[$sParameter]))
			{
				$this->setSetting($sParameter, $this->_aSettings[$sParameter].'?contenido='.$sess->id, true);
			}
		}
	}

	function addPath ($sFile)
	{
		global $cfgClient, $client, $_SERVER;

		// Quick and dirty hack
		if (!preg_match('/^(http|https):\/\/((?:[a-zA-Z0-9_-]+\.?)+):?(\d*)/', $sFile))
		{
			if (preg_match('/^\//', $sFile))
			{
				$sFile = "http://".$_SERVER['HTTP_HOST'].$sFile;
			} else {
				$sFile = $cfgClient[$client]["htmlpath"]["frontend"].$sFile;
			}
		}
		return $sFile;
	}

	function setBaseURL ($sBaseUrl)
	{
		$this->_sBaseURL = $sBaseUrl;
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setConvertURLs ($sConvertUrls)
	{
		$this->setSetting("convert_urls", $sConvertUrls, true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setRelativeURLs ($sRelativeUrls)
	{
		$this->setSetting("relative_urls", $sRelativeUrls, true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setEditorLanguage ($sLanguage)
	{
		if ($sLanguage != "")
		{
			$this->setSetting("language", $sLanguage, true);
		}
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setTextDirection ($sDirection)
	{
		$this->setSetting("directionality", $sDirection, true);

		if ($sDirection == "rtl")
		{
			$this->setSetting("theme_advanced_toolbar_align", "right", true);
		} else {
			$this->setSetting("theme_advanced_toolbar_align", "left", true);
		}
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setToolbarAlign ($sToolbarAlign)
	{
		if ($sToolbarAlign == 'right')
		{
			$this->setSetting("theme_advanced_toolbar_align", "right", true);
		} else
		{
			$this->setSetting("theme_advanced_toolbar_align", "left", true);
		}
	}

	function getScripts ()
	{
		if ($this->_bUseGZIP)
		{
			$sReturn = "\n<!-- tinyMCE -->\n".'<script language="javascript" type="text/javascript" src="'.$this->_sBaseURL.'jscripts/tiny_mce/tiny_mce_gzip.js"></script>';
		} else {
			$sReturn = "\n<!-- tinyMCE -->\n".'<script language="javascript" type="text/javascript" src="'.$this->_sBaseURL.'jscripts/tiny_mce/tiny_mce.js"></script>';
		}

		return $sReturn;
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setTimeformat ($sTimeformat)
	{
		$this->setSetting("plugin_insertdate_timeFormat", $this->convertFormat($sTimeformat), true);
	}

	/**
	 * @deprecated V4.7 - 13.06.2007
	 */
	function setDateformat ($sDateformat)
	{
		$this->setSetting("plugin_insertdate_dateFormat", $this->convertFormat($sDateformat), true);
	}

	function getEditor ()
	{
		global $sess, $cfg, $lang, $client, $idart, $cfgClient;

		// TODO: Check functionality - doesn't seem to have any effect...
		$browserparameters = array("restrict_imagebrowser" => array("jpg", "gif", "jpeg", "png"));
		$sess->register("browserparameters");

		// Contenido-specific: Set article_url_suffix setting as it is used in plugins/advlink/jscripts/functions.js on anchor tags
		$this->setSetting("setupcontent_callback", 'myCustomSetupContent', true);
		$this->setSetting("save_callback", 'cutFullpath', true);


		// Set browser windows
		// Difference between file and image browser is with (file) or without categories/articles (image)
		$oTemplate = new Template;
		$oTemplate->set('s', 'IMAGEBROWSER', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser');
		$oTemplate->set('s', 'FILEBROWSER',	 $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=filebrowser');
		$oTemplate->set('s', 'FLASHBROWSER', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser');
		$oTemplate->set('s', 'MEDIABROWSER', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser');
		$oTemplate->set('s', 'FRONTEND_PATH', $cfgClient[$client]["path"]["htmlpath"]);
		// GZIP support options
		if ($this->_bUseGZIP)
		{
			$sGZIPScript = 	"<script language=\"JavaScript\" type=\"text/javascript\">\n".
							"	tinyMCE_GZ.init({ \n".
							"	plugins : '" . $this->_aSettings["plugins"] . "', \n".
							"	themes : '" . $this->_aSettings["theme"] . "', \n".
							"	languages : '" . $this->_aSettings["language"] . "', \n".
							"	disk_cache : true, \n".
							"	debug : false \n".
							"});\n".
							"</script>\n";
			$oTemplate->set('s', 'COMPRESSOR',  $sGZIPScript);
		} else {
			$oTemplate->set('s', 'COMPRESSOR',  '');
		}

		// Calculate the configuration
		$sConfig = '';

		foreach ($this->_aSettings as $sKey => $sValue)
		{
			if (is_bool($sValue))
			{
				if ($sValue === true)
				{
					$sValue = "true";
				} else {
					$sValue = "false";
				}
			}

			if ($sValue == "true" || $sValue == "false" ||
				$sKey == "oninit" || $sKey == "onpageload")
			{
				$sConfig .= "'$sKey': ".$sValue;
			} else {
				$sConfig .= "'$sKey': '".$sValue."'";
			}
			$sConfig .= ",\n\t";
		}

		$sConfig = substr($sConfig, 0, -3);
		$oTemplate->set('s', 'CONFIG', $sConfig);

		$oTxtEditor = new cHTMLTextarea($this->_sEditorName, $this->_sEditorContent);
		$oTxtEditor->setId($this->_sEditorName);

		$sBgColor = $this->_aSettings["contenido_background_color"];

		$oTxtEditor->setStyle("width: ".$this->_aSettings["width"]."; height: ".$this->_aSettings["height"]."; background-color: ".$sBgColor.";");

		$sReturn  = $oTemplate->generate($cfg['path']['all_wysiwyg'] . $this->_sEditor . "/tinymce.tpl.html", true);
		$sReturn .= $oTxtEditor->render();

		return $sReturn;
	}

    function getConfigInlineEdit() {
        $sConfig = '';
        $this->setToolbar('inline_edit');

		foreach ($this->_aSettings as $sKey => $sValue)
		{
			if (is_bool($sValue))
			{
				if ($sValue === true)
				{
					$sValue = "true";
				} else {
					$sValue = "false";
				}
			}

			if ($sValue == "true" || $sValue == "false" ||
				$sKey == "oninit" || $sKey == "onpageload")
			{
				$sConfig .= "'$sKey': ".$sValue;
			} else {
				$sConfig .= "'$sKey': '".$sValue."'";
			}
			$sConfig .= ",\n\t";
		}

		$sConfig = substr($sConfig, 0, -3);

        return $sConfig;
    }

    function getConfigFullscreen() {
        $sConfig = '';
        $this->setToolbar('fullscreen');

        $sConfig .= "'theme_advanced_buttons1': '".$this->_aSettings['theme_advanced_buttons1']."',\n";
        $sConfig .= "'theme_advanced_buttons2': '".$this->_aSettings['theme_advanced_buttons2']."',\n";
        $sConfig .= "'theme_advanced_buttons3': '".$this->_aSettings['theme_advanced_buttons3']."',\n";
        $sConfig .= "'theme_advanced_toolbar_align': '".$this->_aSettings['theme_advanced_toolbar_align']."',\n";
        $sConfig .= "'plugins': '".$this->_aSettings['plugins']."'\n";

        return $sConfig;
    }
}
?>