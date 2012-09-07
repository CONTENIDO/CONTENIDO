<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Debug Interface
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.3.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-06-04
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.debug.php 346 2008-06-27 10:29:14Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$areaList = new Area();
$areas = $areaList = $areaList->getAvailableAreas();

$areaSelectTemplate= new Template;
$areaSelectTemplate->set('s', 'NAME', 'areaselect');

foreach ($areas as $key => $value)
{
	$areaSelectTemplate->set('d', 'VALUE', $key);
	$areaSelectTemplate->set('d', 'CAPTION', $value['name']);
	$areaSelectTemplate->set('d', 'SELECTED','');
	$areaSelectTemplate->next();
} 

$areaSelector = $areaSelectTemplate->generate($cfg['path']['templates'].$cfg['templates']['generic_select'], true);

$actionList = new Action();
$actions = $actionList = $actionList->getAvailableActions();

$actionSelectTemplate= new Template;
$actionSelectTemplate->set('s', 'NAME', 'actionselect');

foreach ($actions as $key => $value)
{
	$actionSelectTemplate->set('d', 'VALUE', $key);
	$actionSelectTemplate->set('d', 'CAPTION', $value['name']);
	$actionSelectTemplate->set('d', 'SELECTED','');
	$actionSelectTemplate->next();
} 

$actionSelector = $actionSelectTemplate->generate($cfg['path']['templates'].$cfg['templates']['generic_select'], true);

if ($querytype == "areaactionitem")
{
	$res = $perm->have_perm_area_action_item($areaselect, $actionselect, $itemid);
	
	if ($res)
	{
		$result = "has right for have_perm_area_action_item($areaselect, $actionselect, $itemid)";
	} else {
		$result = "has no right for have_perm_area_action_item($areaselect, $actionselect, $itemid)";
	}
}

if ($querytype == "areaaction")
{
	$res = $perm->have_perm_area_action($areaselect, $actionselect);
	
	if ($res)
	{
		$result = "has right for have_perm_area_action($areaselect, $actionselect)";
	} else {
		$result = "has no right for have_perm_area_action($areaselect, $actionselect)";
	}
}
if ($querytype == "area")
{
	$res = $perm->have_perm_area_action($areaselect, 0);
	
	if ($res)
	{
		$result = "has right for have_perm_area_action($areaselect, 0)";
	} else {
		$result = "has no right for have_perm_area_action($areaselect, 0)";
	}
}


echo "<h1>Debug</h1>";
echo "<h4>Check for right:</h4>";
$form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="group_edit">
                 <input type="hidden" name="frame" value="'.$frame.'">
				 <input type="hidden" name="groupid" value="'.$groupid.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';
echo $form;   
echo "Area:".$areaSelector."<br>";
echo "Action:".$actionSelector."<br>";
echo 'Item:<input type="text" name="itemid">';
echo "<br>Type:<br>";
echo "<input type='radio' name='querytype' value='areaactionitem'>have_perm_area_action_item<br>";
echo "<input type='radio' name='querytype' value='areaaction'>have_perm_area_action<br>";
echo "<input type='radio' name='querytype' value='area'>have_perm_area_action without action (i.e. area access right)<br>";
echo "<input type='submit'><br><br>Result:<br>";
echo "<textarea rows=20 cols=80>$result</textarea></form>";
?>