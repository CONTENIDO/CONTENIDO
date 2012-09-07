<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Display languages
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-02
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.lang_overview.php 351 2008-06-27 11:30:37Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$area="lang";

if (!isset($action)) $action = "";

if (!is_numeric($targetclient))
{
	$targetclient = $client;
}

$iGetIdlang = $idlang;

$sql = "SELECT
        *
        FROM
        ".$cfg["tab"]["lang"]." AS A,
        ".$cfg["tab"]["clients_lang"]." AS B
        WHERE
        A.idlang=B.idlang AND
        B.idclient='".Contenido_Security::toInteger($targetclient)."'
        ORDER BY A.idlang";
        
$db->query($sql);

$tpl->set('s','TARGETCLIENT',$targetclient);

$iLangCount = 0;
while ($db->next_record()) {
    $iLangCount++;
    
    $idlang = $db->f("idlang");

    if ($db->f("active") == 0) {
         //activate
        $message = i18n("Activate language");
        $active = "<a title=\"$message\" href=\"".$sess->url("main.php?area=$area&action=lang_activatelanguage&frame=$frame&targetclient=$targetclient&idlang=".$db->f("idlang"))."#clickedhere\"><img src=\"".$cfg["path"]["images"]."offline.gif"."\" border=\"0\" title=\"$message\" alt=\"$message\"></a>";
    } else {
        //deactivate
		$message = i18n("Deactivate language");
        $active = "<a title=\"$message\" class=action href=\"".$sess->url("main.php?area=$area&action=lang_deactivatelanguage&frame=$frame&targetclient=$targetclient&idlang=".$db->f("idlang"))."#clickedhere\"><img src=\"".$cfg["path"]["images"]."online.gif"."\" border=\"0\" title=\"$message\" alt=\"$message\"></a>";
    }

    // Delete Button
    $deleteMsg = sprintf(i18n("Do you really want to delete the language %s?"),htmlspecialchars($db->f("name")));
    $deleteAct = i18n("Delete language");
    $deletebutton = '<a title="'.$deleteAct.'" href="javascript://" onclick="box.confirm(\''.$deleteAct.'\', \''.$deleteMsg.'\', \'deleteLang('.$db->f("idlang").')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$deleteAct.'" alt="'.$deleteAct.'"></a>';

    $bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light"] : $cfg["color"]["table_dark"];
    
    $tpl->set('d', 'BGCOLOR',       $bgcolor);
    $tpl->set('d', 'LANGUAGE',      '<a target="right_bottom" href="'.$sess->url("main.php?area=lang_edit&idlang=$idlang&frame=4").'">'.$db->f("name").'</a>&nbsp;<span style="font-size:10px">('.$idlang.')</span>');
    $tpl->set('d', 'ACTIVATEBUTTON',  $active);
    $tpl->set('d', 'DELETEBUTTON',  $deletebutton);
    //$tpl->set('d', 'ICON', '<a target="right_bottom" href="'.$sess->url("main.php?area=lang_edit&idlang=$idlang&frame=4").'"><img src="images/language.gif" border="0"></a>');
    $tpl->set('d', 'ICON', '');
    
    if ($iGetIdlang == $idlang) {
        $tpl->set('d', 'MARKED', ' id="marked" ');
    } else {
        $tpl->set('d', 'MARKED', '');
    }
    
    $tpl->next();
}

$newlanguageform = '<form name=newlanguage method="post" action="'.$sess->url("main.php?area=$area&frame=$frame").'">
                    '.$sess->hidden_session().'
                    <input type="hidden" name="action" value="lang_newlanguage">
                    <table cellpadding="0" cellspacing="0" border="0">
                    <tr><td class="text_medium">'.i18n("New language").':
                    <INPUT type="text" name="name">&nbsp;&nbsp;&nbsp;
                    <INPUT type="image" src="'.$cfg['path']['images'].'but_ok.gif" border="0">
                    </td></tr></table></from>';

$tpl->set('s', 'NEWLANGUAGEFORM', $newlanguageform);
$tpl->set('s', 'SID', $sess->id);

if ( $tmp_notification ) {

    $noti_html = '<tr><td colspan="3">'.$tmp_notification.'</td></tr>';
    $tpl->set('s', 'NOTIFICATION', $noti_html);

} else {

    $tmp_notification = $notification->returnNotification("info", i18n("Language deleted"));
    
    $noti_html = '<tr><td colspan="3">'.$tmp_notification.'</td></tr>';
    $tpl->set('s', 'NOTIFICATION', '');
    
}

$tpl->set('s', 'LANG_COUNT', $iLangCount);

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_overview']);

?>