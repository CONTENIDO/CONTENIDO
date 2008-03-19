<?php
/**
* $RCSfile$
*
* Description: Step x of installation: Choose plugins to install
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-03-14
* Note: Code design of the Setup routine is such a piece of... Hopefully Setup will be rewritten someday...
* When adding new steps, you need to: 
* modify index.php
* modify /lib/defines.php
* create a class in /steps/forms; 
* create a template in /templates/setup/forms; 
* create file using created class and update stepx.php files in /steps/migration, /steps/setup and /steps/upgrade accordingly that will be moved up
* don't forget to modify /steps/forms/installer.php
* and, if needed, update po/mo files.
* hopefully you're done now...
* }}
*
* $Id$
*/
class cSetupAdditionalPlugins extends cSetupMask
{
	function cSetupAdditionalPlugins ($step, $previous, $next)
	{
		cSetupMask::cSetupMask("templates/setup/forms/additionalplugins.tpl", $step);
		$this->setHeader(i18n("Additional Plugins"));
		$this->_oStepTemplate->set("s", "TITLE", i18n("Additional Plugins"));
		$this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please select Plugins to be installed"));
		
		// add new plugins to this array and you're done.
		$aPlugins = array();
		$aPlugins['plugin_conman'] = array('label' => i18n('ConMan'), 'desc' => i18n('Contenido Contact Manager'));
		$aPlugins['plugin_newsletter'] = array('label' => i18n('Newsletter'), 'desc' => i18n('Contenido Newsletter'));
		$aPlugins['plugin_content_allocation'] = array('label' => i18n('Content Allocation'), 'desc' => i18n('Contenido Content Allocation'));
		
		$sCheckBoxes = '';
		if (sizeof($aPlugins) > 0) {
			foreach ($aPlugins as $sInternalName => $aPluginData) {
				$sChecked = (isset($_SESSION[$sInternalName]) && strval($_SESSION[$sInternalName]) == 'true') ? ' checked="checked"' : '';
				$sCheckBoxes .= '<p class="plugin_select" style="padding-left:2px;"><input type="checkbox" style="vertical-align:middle;border:0;width:auto;" id="'.$sInternalName.'" name="'.$sInternalName.'" value="true"'.$sChecked.'> <label for="'.$sInternalName.'">'.$aPluginData['label'].'<!-- ('.$aPluginData['desc'].')--></label></p>';
			}
		} else {
			$sCheckBoxes = i18n("None available");
		}
		$this->_oStepTemplate->set("s", "PLUGINLIST", $sCheckBoxes);
		
		$this->setNavigation($previous, $next);
	}
		
}

?>