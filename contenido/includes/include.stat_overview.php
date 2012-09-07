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
 *   created 2003-04-29
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id: include.stat_overview.php 366 2008-06-27 14:18:35Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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