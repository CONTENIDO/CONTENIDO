
<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Display files from specified directory
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend includes
 * @version    1.0.1
 * @author     Willi Mann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2004-07-14
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("includes", "functions.file.php");

$tpl->reset();

if (!(int) $client > 0) {
  #if there is no client selected, display empty page
  $oPage = new cPage;
  $oPage->render();
  return;
}

$path = $cfgClient[$client]["tpl"]["path"];
$sFileType = "html";

$sSession = $sess->id;

$sArea = 'htmltpl';
$sActionDelete = 'htmltpl_delete';
$sActionEdit = 'htmltpl_edit';

$sScriptTemplate = '
<script type="text/javascript" src="scripts/rowMark.js"></script>
<script type="text/javascript" src="scripts/general.js"></script>
<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sSession.'"></script>
<script type="text/javascript">

    /* Create messageBox instance */
    box = new messageBox("", "", "", 0, 0);

    function deleteFile(file) 
	{
        url  = "main.php?area='.$sArea.'";
        url += "&action='.$sActionDelete.'";
        url += "&frame=2";
        url += "&delfile=" + file;
        url += "&contenido='.$sSession.'";
        window.location.href = url;
		parent.parent.frames["right"].frames["right_bottom"].location.href = "main.php?area='.$sArea.'&frame=4&contenido='.$sSession.'";
    }
</script>';

$tpl->set('s', 'JAVASCRIPT', $sScriptTemplate);

# delete file
if ($action == $sActionDelete)
{
    if (!strrchr($_REQUEST['delfile'], "/"))
    {
        if (file_exists($path.$_REQUEST['delfile']))
        {
            unlink($path.$_REQUEST['delfile']);
            removeFileInformation($client, $_REQUEST['delfile'], 'templates', $db);
        }
    }

}

if ($handle = opendir($path))
{

    $aFiles = array();
    
    while ($file = readdir($handle))        
    {
        if(substr($file, (strlen($file) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType" AND is_readable($path.$file)) 
        {
            $aFiles[] = $file;		
        }elseif (substr($file, (strlen($file) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType" AND !is_readable($path.$file))
        {
        	$notification->displayNotification("error", $file." ".i18n("is not readable!"));
        }
    }
    closedir($handle);
    
    // display files
    if (is_array($aFiles)) 
    {
    	
    	sort($aFiles);
    	
        foreach ($aFiles as $filename) 
        {
        	          	
            $bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light"] : $cfg["color"]["table_dark"];
            $tpl->set('d', 'BGCOLOR', $bgcolor);
    
            $tmp_mstr = '<a class=\"action\" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')" title="%s" alt="%s">%s</a>';
    
            $html_filename = sprintf($tmp_mstr, 'right_top',
                           $sess->url("main.php?area=$area&frame=3&file=$filename"),
                           'right_bottom',
                           $sess->url("main.php?area=$area&frame=4&action=$sActionEdit&file=$filename&tmp_file=$filename"),
                           $filename, $filename, htmlspecialchars($filename));
                           
            $tpl->set('d', 'FILENAME', $html_filename);
            
            $delTitle = i18n("Delete File");
            $delDescr = sprintf(i18n("Do you really want to delete the following file:<br><br>%s<br>"),$filename);
            
            if ($perm->have_perm_area_action('style', $sActionDelete)) 
            {	    	
            	$tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteFile(\\\''.$filename.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'"></a>');
            }else
            {
            	$tpl->set('d', 'DELETE', '');
            }
            
            if (stripslashes($_REQUEST['file']) == $filename) {
                $tpl->set('d', 'ID', 'id="marked"');
            } else {
                $tpl->set('d', 'ID', '');
            }
            
            $tpl->next();
  
       }
    }
}else
{
    if ((int) $client > 0) {
        $notification->displayNotification("error", i18n("Directory is not existing or readable!")."<br>$path");
    }        
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['files_overview']);

?>