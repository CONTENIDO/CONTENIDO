<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Display log entries
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.5
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created  2003-05-09
 *   modified 2008-06-16, Holger Librenz, Hotfix: added check for invalid calls
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-10-15, Dominik Ziegler, fetching areaname from actions array to save a lot of database queries
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$clientclass = new cApiClientCollection;

$db2 = new DB_Contenido;

if(!$perm->have_perm_area_action($area))
{
  $notification->displayNotification("error", i18n("Permission denied"));
} else {

    $tpl->reset();

    $form = '<form name="log_select" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="log_show">
                 <input type="hidden" name="frame" value="'.$frame.'">';


    $tpl->set('s', 'FORM', $form);
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'SELECTBGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SELECTBBGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('s', 'HEADERBGCOLOR', $cfg["color"]["table_header"]);
    $tpl->set('s', 'RHEADERBGCOLOR', $cfg["color"]["table_header"]);
    $tpl->set('s', 'SUBMITTEXT', i18n("Submit query"));
    $tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4"));


    $userclass = new Users();
    $structureclass = new Structure();
    $artclass = new Art();
    $actionclass = new cApiActionCollection();

    $clients = $clientclass->getAccessibleClients();
    $users = $userclass->getAccessibleUsers(explode(',', $auth->auth['perm']));
    $userselect = "<option value=\"%\">".i18n("All users")."</option>";
    $actions = $actionclass->getAvailableActions();
    $actionselect = "<option value=\"%\">".i18n("All actions")."</option>";
   	$clientList = $clientclass->getAccessibleClients();

   	foreach ($clientList as $key=>$value) {
        if (strcmp($idqclient,$key) == 0) {
            $selected = "SELECTED";
        } else {
            $selected = "";
        }

        $clientselect .= "<option value=\"".$key."\" ".$selected.">".$value["name"]."</option>";
    }

    foreach ($users as $key=>$value) {
        if (strcmp($idquser,$key) == 0) {
            $selected = "SELECTED";
        } else {
            $selected = "";
        }

        $userselect .= "<option value=\"".$key."\" ".$selected.">".$value["username"]." (".$value["realname"].")</option>";
    }
	
	foreach ($actions as $key=>$value) {
        if (strcmp($idqaction,$key) == 0) {
            $selected = "SELECTED";
        } else {
            $selected = "";
        }
		
        // $areaname = $classarea->getAreaName($actionclass->getAreaForAction($value["name"]));
        $areaname = $value["areaname"];
		$actionDescription = $lngAct[$areaname][$value["name"]];

        if ($actionDescription == "")
        {
            $actionDescription = $value["name"];
        }

        $actionselect .= "<option value=\"".$key."\" ".$selected.">".$value["name"]." (".$actionDescription.")"."</option>";
    }

	$days = array();

	for ($i = 1; $i < 32; $i ++)
	{
		$days[$i] = $i;
	}

	$months = array();

	for ($i = 1; $i < 13; $i++)
	{
		$months[$i] = $i;
	}

	$years = array();
	for ($i = 2000; $i < 2020; $i++)
	{
		$years[$i] = $i;
	}

	$fromday = new cHTMLSelectElement("fromday");
	$fromday->autoFill($days);

	if ($_REQUEST["fromday"] > 0)
	{
		$fromday->setDefault($_REQUEST["fromday"]);
	} else {
		$fromday->setDefault(date("j"));
	}
	$today = new cHTMLSelectElement("today");
	$today->autoFill($days);

	if ($_REQUEST["today"] > 0)
	{
		$today->setDefault($_REQUEST["today"]);
	} else {
		$today->setDefault(date("j"));
	}

	$frommonth = new cHTMLSelectElement("frommonth");
	$frommonth->autoFill($months);

	if ($_REQUEST["frommonth"] > 0)
	{
		$frommonth->setDefault($_REQUEST["frommonth"]);
	} else {
		$frommonth->setDefault(date("n"));
	}

	$tomonth = new cHTMLSelectElement("tomonth");
	$tomonth->autoFill($months);

	if ($_REQUEST["tomonth"] > 0)
	{
		$tomonth->setDefault($_REQUEST["tomonth"]);
	} else {
		$tomonth->setDefault(date("n"));
	}

	$fromyear = new cHTMLSelectElement("fromyear");
	$fromyear->autoFill($years);

	if ($_REQUEST["fromyear"] > 0)
	{
		$fromyear->setDefault($_REQUEST["fromyear"]);
	} else {
		$fromyear->setDefault(date("Y"));
	}

	$toyear = new cHTMLSelectElement("toyear");
	$toyear->autoFill($years);

	if ($_REQUEST["toyear"] > 0)
	{
		$toyear->setDefault($_REQUEST["toyear"]);
	} else {
		$toyear->setDefault(date("Y"));
	}

	$entries = array();
	$entries[0] = i18n("Unlimited");
	$entries[10] = "10 ". i18n("Entries");
	$entries[20] = "20 ". i18n("Entries");
	$entries[30] = "30 ". i18n("Entries");
	$entries[50] = "50 ". i18n("Entries");
	$entries[100] = "100 ". i18n("Entries");

	$olimit = new cHTMLSelectElement("limit");
	$olimit->autoFill($entries);

	if (isset($_REQUEST["limit"]))
	{
		$olimit->setDefault($_REQUEST["limit"]);
	} else {
		$olimit->setDefault(10);
	}

    $tpl->set('s', 'USERS', $userselect);
    $tpl->set('s', 'CLIENTS', $clientselect);
    $tpl->set('s', 'ACTION', $actionselect);
    $tpl->set('s', 'FROMDAY', $fromday->render());
    $tpl->set('s', 'FROMMONTH', $frommonth->render());
    $tpl->set('s', 'FROMYEAR', $fromyear->render());
    $tpl->set('s', 'TODAY', $today->render());
    $tpl->set('s', 'TOMONTH', $tomonth->render());
    $tpl->set('s', 'TOYEAR', $toyear->render());
    $tpl->set('s', 'LIMIT', $olimit->render());

    $fromdate = $fromyear->getDefault()."-".$frommonth->getDefault()."-".$fromday->getDefault()." 00:00:00";
    $todate = $toyear->getDefault()."-".$tomonth->getDefault()."-".$today->getDefault()." 23:59:59";

    if ($limit == 0)
    {
        $limitsql = "";
    } else {
        $limitsql = "LIMIT ".Contenido_Security::escapeDB($limit, $db);
    }

	if ($idquser == "%")
	{
		$users = $userclass->getAccessibleUsers(explode(',', $auth->auth['perm']));

		foreach ($users as $key=>$value) {
			$userarray[] = $key;
		}

      	$uservalues = implode('", "',$userarray);
		$userquery = 'IN ("'.$uservalues.'")';
	} else {
		$userquery = "LIKE '".$idquser."'";
	}

     $sql = 'SELECT
                idlog,
                user_id,
                idaction,
		        idlang,
		        idclient,
                idcatart,
                logtimestamp
            FROM
              '. $cfg["tab"]["actionlog"] . '
            WHERE
                user_id '.$userquery.' AND
                idaction LIKE "'.Contenido_Security::escapeDB($idqaction, $db).'" AND
                logtimestamp > "'.Contenido_Security::escapeDB($fromdate, $db).'" AND
                logtimestamp < "'.Contenido_Security::escapeDB($todate, $db).'" AND
                idclient LIKE "'.Contenido_Security::escapeDB($idqclient, $db).'"
                ORDER BY logtimestamp DESC '
                . $limitsql;

    $db->query($sql);

    if ($db->affected_rows() == 0)
    {
        $noresults = '<tr class="text_medium" style="background-color: '.$bgcolor.';" >'.
                     '<td valign="top" colspan="6" style="border: 0px; border-top:1px; border-right:1px;border-color: '.$cfg["color"]["table_border"].'; border-style: solid;">'.i18n("No results").'</td></tr>';

    } else {
        $noresults = "";
    }

    $tpl->set('s', 'NORESULTS', $noresults);

    while ($db->next_record())
    {

        $darkrow = !$darkrow;

        if ($darkrow)
        {
            $bgcolor = $cfg["color"]["table_dark"];
        } else {
            $bgcolor = $cfg["color"]["table_light"];
        }

		$structureName = $structureclass->getStructureName($structureclass->getStructureIDForCatArt($db->f("idcatart")),$db->f("idlang"));
		$artName = $artclass->getArtName($artclass->getArtIDForCatArt($db->f("idcatart")),$db->f("idlang"));

        if ($structureName == "") { $structureName = "-"; }
        if ($artName == "") { $artName = "-"; }

		$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl->set('d', 'RBGCOLOR', $bgcolor);
        $tpl->set('d', 'RCLIENT', $clientList[$db->f("idclient")]["name"]);
        $tpl->set('d', 'RDATETIME', $db->f("logtimestamp"));
        $tpl->set('d', 'RUSER' , $users[$db->f("user_id")]["username"]);
        $areaname = $classarea->getAreaName($actionclass->getAreaForAction($db->f("idaction")));
        $actionDescription =  $lngAct[$areaname][$actionclass->getActionName($db->f("idaction"))];
        if ($actionDescription == "")
        {
            $actionDescription = $actionclass->getActionName($db->f("idaction"));
        }
        $tpl->set('d', 'RACTION', $actionDescription );
        $tpl->set('d', 'RSTR', $structureName);
        $tpl->set('d', 'RPAGE', $artName);

        $tpl->next();

    }

    # Generate template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['log_main']);

}
?>
