<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Displays languages
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-23
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id: include.stat_menu.php 366 2008-06-27 14:18:35Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->reset();

$currentLink = '<a target="right_bottom" href="'.$sess->url("main.php?area=stat&frame=4&displaytype=top10&action=stat_show&yearmonth=current").'">'.i18n("Current Report").'</a>';

$availableYears = statGetAvailableYears($client,$lang);

// Title
$bgcolor = "#FFFFFF";
$tpl->set('s', 'OVERVIEWBGCOLOR', $cfg["color"]["table_header"]);
$tpl->set('s', 'PADDING_LEFT', '17');
$tpl->set('s', 'OVERVIEWTEXT', "<b>".i18n("Statistics Overview")."</b>");

// Current Statistic
$bgcolor = $cfg["color"]["table_light"];
$tpl->set('s', 'CURRENTBGCOLOR', $bgcolor);
$tpl->set('s', 'CURRENTTEXT', $currentLink);
$tpl->set('s', 'PADDING_LEFT', '17');

// Empty Row
$bgcolor = '#FFFFFF';
$tpl->set('s', 'ARCHIVEBGCOLOR', $cfg["color"]["table_header"]);
$tpl->set('s', 'ARCHIVETEXT', '<b>'.i18n("Archived Statistics").'</b>');
$tpl->set('s', 'PADDING_LEFT', '17');

foreach ($availableYears as $yearIterator)
{
        //$yearLink = function statsOverviewYear($year)
        $dark = !$dark;
        $yearLink = '<a target="right_bottom" href="'.$sess->url("main.php?area=stat&frame=4&action=stat_show&displaytype=top10&showYear=1&year=".$yearIterator).'">'."$yearIterator".'</a>';
        if ($dark) {
            $bgcolor = $cfg["color"]["table_dark"];
        } else {
            $bgcolor = $cfg["color"]["table_light"];
        }
        $tpl->set('d', 'BGCOLOR', $bgcolor);
        $tpl->set('d', 'TEXT', $yearLink);
        $tpl->set('d', 'PADDING_LEFT', '17');
        $tpl->next();
        
        $availableMonths = statGetAvailableMonths($yearIterator,$client,$lang);
        
        foreach ($availableMonths as $monthIterator)
        {
                $monthCanonical = statReturnCanonicalMonth($monthIterator);
                $monthLink = '<a target="right_bottom" href="'.$sess->url("main.php?area=stat&frame=4&action=stat_show&displaytype=top10&yearmonth=".$yearIterator . $monthIterator).'">'."$monthCanonical".'</a>';

                $dark = !$dark;
                
                if ($dark) {
                   $bgcolor = $cfg["color"]["table_dark"];
                } else {
                    $bgcolor = $cfg["color"]["table_light"];
                }
                
                $tpl->set('d', 'BGCOLOR', $bgcolor);
                $tpl->set('d', 'TEXT', $monthLink);
                $tpl->set('d', 'PADDING_LEFT', '20');
                $tpl->next();
        }
}

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_menu']);
?>