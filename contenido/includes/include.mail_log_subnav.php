<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Builds the third navigation layer
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.0
 * @author     Oliver Lohkemper
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.14
 *
 * {@internal
 *   created 2010-08-23
 *
 *   $Id: include.default_subnav.php 2402 2012-06-25 23:01:35Z xmurrix $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK'))
    die('Illegal call');


//In some cases dont print menue
if($dont_print_subnav == 1) {
    $tpl->reset();
    $tpl->generate( $cfg["path"]["templates"] . $cfg['templates']['right_top_blank'] );
    return;
}

$aExectime = array();
$aExectime["fullstart"] = getmicrotime();

/*
 * Ben�tigt alle m�glichen vom Frame �bergenene GET-Parameter-Names
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

    foreach( $_GET as $sTempKey => $sTempValue )
    {
        if( in_array($sTempKey, $aBasicParams) )
        {
            /* Basic parameters attached */
            $iCountBasicVal++;
        }
        else if( ( substr($sTempKey,0,2)=='id' || substr($sTempKey, -2, 2)=='id' )
              && ( (int)$sTempValue==$sTempValue                      // check integer
                    || preg_match("/^[0-9a-f]{32}$/", $sTempValue) ) // check md5
                   )
        {
            /* complement the selected data */
            $sUrlParams.= '&'.$sTempKey.'='.$sTempValue;
        }
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
cDebug('Url-Params: '.$sUrlParams);


/*
 * Select NavSubItems from DB
 */
    $nav = new Contenido_Navigation;

    $sql = "SELECT
                navsub.location AS location,
                area.name       AS name,
                area.menuless   AS menuless
            FROM
                ".$cfg["tab"]["area"]."    AS area,
                ".$cfg["tab"]["nav_sub"]." AS navsub
            WHERE
                area.idarea = navsub.idarea
              AND
                navsub.level = 1
              AND
                navsub.online = 1
              AND (
                    area.parent_id = '".$area."'
                OR
                    area.name = '".$area."'
                )
            ORDER BY
                area.parent_id ASC,
                navsub.idnavs ASC";

/* Debug */
cDebug($sql);

    $db->query($sql);


    while( $db->next_record() )
    {
        /* Name */
        $sArea = $db->f("name");
        /* Set translation path */
        $sCaption = $nav->getName( $db->f("location") );
		//echo $sArea . ' = ' . $area;
        /* for Main-Area*/
        if( $sArea == $area )
        {
            /* Menueless */
            $bMenuless = $db->f("menuless") ? true : false;

            if( $bVirgin && !$bMenuless && $db->f("name") == $area )
            {
                // ist loading fron Main, Main-Area and Menuless -> stop this "while"
                break;
            }
        }




        /* Link */
        $sLink = $sess->url("main.php?area=".$sArea."&frame=4".($appendparameters?'&appendparameters='.$appendparameters:'')."&contenido=".$sess->id.$sUrlParams);


        /* Class */
        if($sArea == $area)
            $sClass = ' current';
        else
            $sClass = '';
            
         $sClass .=' '.  $sArea;

        /* fill template */
        $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt );
        $tpl->set("d", "CLASS",     'item '.$sArea );
        $tpl->set("d", "CAPTION",   '<a  class="white '.$sClass.'" onclick="" target="right_bottom" href="'.$sLink.'">'.$sCaption.'</a>');
        $tpl->next();
    }

    //Have area a menue
    if($db->num_rows() == 0) {

        $sql = sprintf("SELECT menuless FROM %s WHERE name = '%s' AND parent_id = 0", $cfg["tab"]["area"], $area);
        $db->query($sql);

        while($db->next_record()) {
            $bMenuless = $db->f("menuless") ? true : false;

        }
    }

    if(!$bMenuless)
    {
        $tpl->set('s', 'CLASS', $bMenuless ? 'menuless' : '');
        $tpl->set('s', 'SESSID', $sess->id);

        $sTpl = $tpl->generate( $cfg["path"]["templates"] . $cfg['templates']['default_subnav'], true );

        cDebug('sExectime: '.substr($sExectime,0,7)." sec");

        echo $sTpl;
    }
    else
    {
        /*
         * Is loading from main.php
         */
        $tpl->reset();
        $tpl->generate( $cfg["path"]["templates"] . $cfg['templates']['right_top_blank'] );
    }

?>
