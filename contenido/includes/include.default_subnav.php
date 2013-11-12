<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Builds the third navigation layer
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Oliver Lohkemper
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.14
 *
 * {@internal
 *   created 2010-08-23
 *
 *   $Id: include.default_subnav.php 338 2008-06-27 09:02:23Z frederic.schneider $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK'))
	die('Illegal call');


$aExectime = array();
$aExectime["fullstart"] = getmicrotime();

/*
 * Debug-Modus: on / off
 * and Message as String
 */
$bDebug = false;
$sDebugMsg = '';

/*
 * Benötigt alle möglichen vom Frame übergenene GET-Parameter-Names
 */
$aBasicParams = array( 'area', 'frame', 'contenido', 'appendparameters' );

/*
 * Flag to check is file is loading from Main-Frame
 */
$bVirgin = false;


$area = Contenido_Security::escapeDB($area, $db);


/*
 * Basic-Url-Params with
 * Key: like 'id%' or '%id'
 * and
 * Value: are integer or strlen=32 (for md5)
 */
$sUrlParams = ''; # URL-Parameter as string "&..." + "&..."
$iCountBasicVal = 0; # Count of basic Parameter in URL

foreach ($_GET as $sTempKey => $sTempValue) {
	if (in_array($sTempKey, $aBasicParams)) {
		/* Basic parameters attached */
		$iCountBasicVal++;
	} else if ((substr($sTempKey,0,2)=='id' || substr($sTempKey, -2, 2)=='id')
		  && ((int)$sTempValue==$sTempValue 					 // check integer
				|| preg_match("/^[0-9a-f]{32}$/", $sTempValue)) // check md5
		  	 )
	{
		/* complement the selected data */
		$sUrlParams.= '&'.$sTempKey.'='.$sTempValue;
	}
}

/*
 * is loading from main.php
 * dann ist die Anzahl aller gültigen Variablen mit den in GET identisch
 */
if ($iCountBasicVal == count($_GET)) {
	$bVirgin = true;
}

/*
 * Area-Url-Params
 *
 * for special params
 *
	switch( $area ) {
		case 'style': case 'js': case 'htmltpl':
			if(array_key_exists('file', $_GET)) {
				$sUrlParams.= '&file='.$_GET['file'];
			}
			break;
		default: echo "";
	}
*/

/* Debug */
$sDebugMsg.= 'Url-Params: '.$sUrlParams."\n";

$areasNavSubs = getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];
    $caption = $areasNavSub['caption'];

	/* for Main-Area*/
	if ($areaName == $area) {
		/* Menueless */
		$bMenuless = $db->f("menuless") ? true : false;

		if ($bVirgin && !$bMenuless && $db->f("name") == $area) {
			// ist loading fron Main, Main-Area and Menuless -> stop this "while"
			break;
		}
	}

	/* Link */
	$sLink = $sess->url("main.php?area=" . $areaName . "&frame=4" . ($appendparameters ? '&appendparameters=' . $appendparameters : '') . $sUrlParams);

	/* Class */
	if ($areaName == $area) {
		$sClass = ' current';
	} else {
		$sClass = '';
	}

	/* fill template */
	$tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
	$tpl->set("d", "CLASS", 'item '.$areaName);
	$tpl->set("d", "CAPTION", '<a class="white'.$sClass.'" onclick="sub.clicked(this)" target="right_bottom" href="'.$sLink.'">'.$caption.'</a>');
	$tpl->next();
}

//Has area a menu?
if ($db->num_rows() == 0) {
    $sql = sprintf("SELECT menuless FROM %s WHERE name = '%s' AND parent_id = 0", $cfg["tab"]["area"], $area);
    $db->query($sql);
    while ($db->next_record()) {
        $bMenuless = $db->f("menuless") ? true : false;
    }
}

if (!$bVirgin || $bMenuless) {
	$tpl->set('s', 'CLASS', $bMenuless ? 'menuless' : '');
	$tpl->set('s', 'SESSID', $sess->id);

	$sTpl = $tpl->generate( $cfg["path"]["templates"] . $cfg['templates']['default_subnav'], true );

	if ($bDebug === true) {
		$aExectime["fullend"] = getmicrotime();
		$sExectime = ($aExectime["fullend"] - $aExectime["fullstart"]);
		$sDebugMsg.= 'sExectime: '.substr($sExectime,0,7)." sec"."\n";

		$sTpl = str_replace( '</body>', '<div style="position:absolute; right:15px; width: 200px; top:0px; height: 32px; overflow: scroll; background:#fff; color:#000; border:1px dotted #f00; padding:2px;">'.nl2br( $sDebugMsg ).'</div>'.'</body>', $sTpl );
	}

	echo $sTpl;
} else {
	/*
	 * Is loading from main.php
	 */
	$tpl->reset();
	$tpl->generate( $cfg["path"]["templates"] . $cfg['templates']['right_top_blank'] );
}

?>