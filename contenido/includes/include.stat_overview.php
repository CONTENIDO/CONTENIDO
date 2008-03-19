<?php
/******************************************
* File      :   include.stat_overview.php
* Project   :   Contenido
* Descr     :   Displays languages
*
* Author    :   Olaf Niemann
* Created   :   23.04.2003
* Modified  :   29.04.2003
*
* © four for business AG
*****************************************/


$tpl->reset();

if ($action == "stat_show")
{

        if (strlen($yearmonth) < 4)
        {
            $yearmonth = "current";
        }

        switch ($displaytype)
        {
           case "all":
                $stattype = i18n("Full statistics");
                break;
           case "top10":
                $stattype = i18n("Top 10");
                break;
           case "top20":
                $stattype = i18n("Top 20");
                break;
           case "top30":
                $stattype = i18n("Top 30");
                break;
           default:
                $displaytype = "all";
                $stattype = i18n("Full statistics");
                break;
        }

        $tpl->set('s', 'SELF_URL', $sess->url("main.php?area=stat&frame=4&idcat=$idcat"));
        if ($showYear == 1)
        {
            $tpl->set('s', 'DROPDOWN', statDisplayYearlyTopChooser($displaytype));
            $tpl->set('s', 'YEARMONTH', '<form name="hiddenValues"><input type="hidden" name="yearmonth" value="'.$year.'"></form>');
        } else {
            $tpl->set('s', 'DROPDOWN', statDisplayTopChooser($displaytype));
            $tpl->set('s', 'YEARMONTH', '<form name="hiddenValues"><input type="hidden" name="yearmonth" value="'.$yearmonth.'"></form>');
        }

        if ($showYear == 1)
        {
                $tpl->set('s', 'STATTITLE', i18n("Yearly").' '.$stattype . " " .$year);
        } else {
            if (strcmp($yearmonth,"current")==0)
            {
                $tpl->set('s', 'STATTITLE', i18n("Current"). ' '.$stattype);
            } else {
                $tpl->set('s', 'STATTITLE', $stattype." ".statReturnCanonicalMonth(substr($yearmonth, 4,2)).' '.substr($yearmonth,0,4));
            }
        }

        $tpl->set('s', 'BGCOLOR', '#FFFFFF');
        $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl->set('s', 'TITLEBGCOLOR', $cfg["color"]["table_header"]);
        $tpl->set('s', 'TITLETEXT', i18n("Title"));
        $tpl->set('s', 'TITLESTATUS', i18n("Status"));
        $tpl->set('s', 'TITLENUMBEROFARTICLES', i18n("Number of articles"));
        $tpl->set('s', 'TITLETOTAL',i18n("Hits"));
        $tpl->set('s', 'TITLEPADDING_LEFT',"5");
        $tpl->set('s', 'TITLEINTHISLANGUAGE', i18n("Hits in this language"));

        switch ($displaytype)
        {
            case "all":
            default:
                if ($showYear == 1)
                {
                    statsOverviewYear($year);
                } else {
                    statsOverviewAll($yearmonth);
                }
                $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_overview']);
                break;
                
            case "top10":
                if ($showYear == 1)
                {
                    statsOverviewTopYear($year,10);
                }else {
                    statsOverviewTop($yearmonth,10);
                }

                $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
                break;
                
            case "top20":
                if ($showYear == 1)
                {
                    statsOverviewTopYear($year,20);
                }else {
                    statsOverviewTop($yearmonth,20);
                }
                
                $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
                break;
                
            case "top30":
                if ($showYear == 1)
                {
                    statsOverviewTopYear($year,30);
                }else {
                    statsOverviewTop($yearmonth,30);
                }
                $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
                break;
        }

} else {
        $tpl->reset();
        $tpl->set('s', 'CONTENTS', '');
        $tpl->generate($cfg['path']['templates'] . $cfg['templates']['blank']);
}
?>
