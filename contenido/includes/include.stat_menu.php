<?php
/******************************************
* File      :   include.stat_overview.php
* Project   :   Contenido
* Descr     :   Displays languages
*
* Author    :   Olaf Niemann
* Created   :   23.04.2003
* Modified  :   23.04.2003
*
* © four for business AG
*****************************************/



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
