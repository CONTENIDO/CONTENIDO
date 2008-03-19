<?php
/**
 * @file Navigation_main_new.output.php 
 * 
 * @project Germany.co.uk
 * @version	1.0.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 09.11.2005
 * @modified 14.11.2005
 */

# see config.local.php!

$oFrontendNavigation->_bDebug = false;
$aNavigationTop = $oFrontendNavigation->getSubCategories(getEffectiveSetting('navigation', 'category_main'));

#echo "<pre>aNavigationTop<br>"; print_r($aNavigationTop); echo "</pre>";

$search_prevent = array('\'', '"');
$replace_prevent = array('', '');

$sHTMLTemplateLevel_1_Active = '<li><a class="active" href="{HREF}" title="Goto: {TITLE}">{NAME}</a></li>';
$sHTMLTemplateLevel_1_InActive = '<li><a href="{HREF}" title="Goto: {TITLE}"}>{NAME}</a></li>';

# generate first level
$sHMTLNavigationLevel1 = '';
if ($idcat == getEffectiveSetting('navigation', 'category_main')) 
{
	$sHMTLNavigationLevel1 .= '<li><a class="active" href="'.$cfgClient[$client]['path']['htmlpath'] . 'index.html'.'" title="Goto: Doorpage">Home</a></li>';
} else {
	$sHMTLNavigationLevel1 .= '<li><a href="'.$cfgClient[$client]['path']['htmlpath'] . 'index.html'.'" title="Goto: Doorpage">Home</a></li>';
}

for ($i = 0; $i < count($aNavigationTop); $i++)
{
	$sCategoryName = $oFrontendNavigation->getCategoryName($aNavigationTop[$i]);
	$sUrl = $oFrontendNavigation->getURL($aNavigationTop[$i], 0, 'urlpath', true, 1);
	
	
	if ($aNavigationTop[$i] == $idcat OR $aNavigationTop[$i] == $oFrontendNavigation->getParent($idcat) OR $aNavigationTop[$i] == $oFrontendNavigation->getParent($oFrontendNavigation->getParent($idcat)))
	{
		$sHMTLNavigationLevel1 .= str_replace(array('{HREF}', '{NAME}', '{TITLE}'), array($sUrl, $sCategoryName, str_replace($search_prevent, $replace_prevent, $sCategoryName)) , $sHTMLTemplateLevel_1_Active);
	}else
	{
		$sHMTLNavigationLevel1 .= str_replace(array('{HREF}', '{NAME}', '{TITLE}'), array($sUrl, $sCategoryName, str_replace($search_prevent, $replace_prevent, $sCategoryName)) , $sHTMLTemplateLevel_1_InActive);
	}
}
# print first level
print '<div id="nav_first"><ul>'.$sHMTLNavigationLevel1.'</ul></div>';

$iLevel = $oFrontendNavigation->getLevel($idcat);

if ($iLevel == 1)
{
	$aNavigationLevel2 = $oFrontendNavigation->getSubCategories($idcat);
}elseif($iLevel == 2)
{
	$aNavigationLevel2 = $oFrontendNavigation->getSubCategories($oFrontendNavigation->getParent($idcat));
}

# generate second level
$sHMTLNavigationLevel2 = '';
for ($i = 0; $i < count($aNavigationLevel2); $i++)
{
	$sCategoryName = $oFrontendNavigation->getCategoryName($aNavigationLevel2[$i]);
	$sUrl = $oFrontendNavigation->getURL($aNavigationLevel2[$i], 0, 'urlpath', true, 1);
	
	if ($aNavigationLevel2[$i] == $idcat OR $aNavigationLevel2[$i] == $oFrontendNavigation->getParent($idcat))
	{
		$sHMTLNavigationLevel2 .= str_replace(array('{HREF}', '{NAME}', '{TITLE}'), array($sUrl, $sCategoryName, str_replace($search_prevent, $replace_prevent, $sCategoryName)) , $sHTMLTemplateLevel_1_Active);
	}else
	{
		$sHMTLNavigationLevel2 .= str_replace(array('{HREF}', '{NAME}', '{TITLE}'), array($sUrl, $sCategoryName, str_replace($search_prevent, $replace_prevent, $sCategoryName)) , $sHTMLTemplateLevel_1_InActive);
	}
}
# print second level
if (strlen($sHMTLNavigationLevel2) > 0)
{
	print '<div id="nav_second"><ul>'.$sHMTLNavigationLevel2.'</ul></div>';
}else
{
	print '<div id="nav_second">&nbsp;</div>';
}

?>