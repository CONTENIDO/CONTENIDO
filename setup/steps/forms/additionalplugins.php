<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Step x of installation: Choose plugins to install
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_notice 
 * Code design of the Setup routine is such a piece of... Hopefully Setup will be rewritten someday...
 * When adding new steps, you need to: 
 * modify index.php
 * modify /lib/defines.php
 * create a class in /steps/forms; 
 * create a template in /templates/setup/forms; 
 * create file using created class and update stepx.php files in /steps/migration, /steps/setup and /steps/upgrade accordingly that will be moved up
 * don't forget to modify /steps/forms/installer.php
 * and, if needed, update po/mo files.
 * hopefully you're done now...
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2008-03-14
 *   modified 2008-03-25, Timo Trautmann, integrated function checkExistingPlugin() which checks if a plugin is already installed
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: additionalplugins.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class cSetupAdditionalPlugins extends cSetupMask
{
	function cSetupAdditionalPlugins ($step, $previous, $next)
	{
        $db = new DB_Contenido($_SESSION["dbhost"], $_SESSION["dbname"], $_SESSION["dbuser"], $_SESSION["dbpass"]);
        
		cSetupMask::cSetupMask("templates/setup/forms/additionalplugins.tpl", $step);
		$this->setHeader(i18n("Additional Plugins"));
		$this->_oStepTemplate->set("s", "TITLE", i18n("Additional Plugins"));
		$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please select Plugins to be installed"));
        
		// add new plugins to this array and you're done.
		$aPlugins = array();
		$aPlugins['plugin_newsletter'] = array('label' => i18n("Newsletter"), 'desc' => i18n("Newsletterfunctionality for dispatching text newsletters and HTML-Newsletters, extensible with professional newsletter extensions. Definition of newsletter recipients and groups of recipients. Layout design of the HTML-Newsletters by Contenido articles."));
		$aPlugins['plugin_content_allocation'] = array('label' => i18n("Content Allocation"), 'desc' => i18n("For the representation and administration of content, 4fb developed the Content Allocation and content include technology. This technology dynamically allows on basis of a Template, to put the content in different places and in different formats according to several criteria."));
		$aPlugins['plugin_mod_rewrite'] = array('label' => i18n("Mod Rewrite"), 'desc' => i18n("Creates so called Clean URLs for a Contenido installation"));
		
		$sCheckBoxes = '';
		if (sizeof($aPlugins) > 0) {
			foreach ($aPlugins as $sInternalName => $aPluginData) {
				$sChecked = ((isset($_SESSION[$sInternalName]) && strval($_SESSION[$sInternalName]) || checkExistingPlugin($db, $sInternalName)) == 'true') ? ' checked="checked"' : '';
				$sCheckBoxes .= '<p class="plugin_select">
                                     <input type="checkbox" class="plugin_checkbox" id="'.$sInternalName.'" name="'.$sInternalName.'" value="true"'.$sChecked.'> 
                                     <label for="'.$sInternalName.'">'.$aPluginData['label'].'</label>
                                     <a href="javascript://" onclick="showPluginInfo(\''.$aPluginData['label'].'\', \''.$aPluginData['desc'].'\');">
                                         <img src="../contenido/images/info.gif" alt="'.i18n("More information").'" title="'.i18n("More information").'" class="plugin_info">
                                     </a>
                                 </p>';
			}
		} else {
			$sCheckBoxes = i18n("None available");
		}
		$this->_oStepTemplate->set("s", "PLUGINLIST", $sCheckBoxes);
		
		$this->setNavigation($previous, $next);
	}
		
}

?>