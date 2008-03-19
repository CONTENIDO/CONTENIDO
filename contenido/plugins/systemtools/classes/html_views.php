<?php
/**
 * Plugin Systemtools
 *
 * @file html_views.php
 * @project Contenido
 * 
 * @version	1.0.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 31.08.2005
 * @modified 19.10.2005
 */

include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/systemfunctions.php');
include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/Controls.php');

include_once($cfg['path']['contenido'].$cfg['path']['includes'].'functions.general.php');

if (!function_exists('human_readable_size'))
{
	function human_readable_size ( $number )
	{
		$base = 1024;
		$suffixes = array( " B", " KB", " MB", " GB", " TB", " PB", " EB" );
	
		$usesuf = 0;
		$n = (float) $number; //Appears to be necessary to avoid rounding
		while( $n >= $base )
		{
			$n /= (float) $base;
			$usesuf++;
		}
		
		$places = 2 - floor( log10( $n ) );
		$places = max( $places, 0 );
		$retval = number_format( $n, $places, ".", "" ) . $suffixes[$usesuf];
		return $retval;
	}
}


/**
 * param $aTableStatusInformation Array with objects of type
 * stdClass Object
 * (
 *     [Name] => con_actionlog
 *     [Type] => MyISAM
 *     [Row_format] => Dynamic
 *     [Rows] => 309
 *     [Avg_row_length] => 65
 *     [Data_length] => 20152
 *     [Max_data_length] => 4294967295
 *     [Index_length] => 1024
 *     [Data_free] => 0
 *     [Auto_increment] => 
 *     [Create_time] => 2005-08-30 17:11:17
 *     [Update_time] => 2005-08-31 17:04:26
 *     [Check_time] => 2005-08-31 11:08:53
 *     [Create_options] => 
 *     [Comment] => 
 * )
 */ 
function generateTableOptions(&$aTableStatusInformation, $bDebug = false)
{
	
	$sHTMLTemplate = '<input type="checkbox" name="selected_tables[]" value="{table_name}"> <b>{table_name}</b> ({num_of_records} Records) {size}<br />';	
	
	if (is_array($aTableStatusInformation) AND count($aTableStatusInformation) > 0)
	{
		$sHTMLOutput = '';
		for ($i = 0; $i < count($aTableStatusInformation); $i++)
		{
			$oRow = &$aTableStatusInformation[$i];
			if ($bDebug) {print "<pre>TableStatusInformation "; print_r($oRow); print "</pre>";}
			
			$floatFormattedData_length = human_readable_size($oRow->Data_length);
			$floatFormattedIndex_length = human_readable_size($oRow->Index_length);
			$floatFormattedSum = human_readable_size($oRow->Data_length + $oRow->Index_length);
			$sSize = '<span style="color: #006600;">data length:</span> '.$floatFormattedData_length.' <span style="color: #006600;">index length:</span> '.$floatFormattedIndex_length.' <span style="color: #006600;">sum:</span> <b>'.($floatFormattedSum).'</b>';
			
			$sHTMLOutput .= str_replace(array('{table_name}', '{num_of_records}', '{size}'), array($oRow->Name, $oRow->Rows, $sSize) , $sHTMLTemplate);
		}
		return $sHTMLOutput;
	}else
	{
		return '';
	}
}
				
function generateFileOverview($aFiles, $sPath , $sActionName, $sActionValue, $sActionName2, $sActionValue2, $area, $frame, $sess, $bDebug = false)
{
	$oControls = new Controls();
	if ($bDebug) {print "<pre>aFiles "; print_r($aFiles); print "</pre>";}
    if (is_array($aFiles) AND count($aFiles) > 0) 
    {
    	$aFiles = array_csort($aFiles, 'file', 'SORT_ASC');
    	
    	$sHTMLOutput = '';
    	for ($i = 0; $i < count($aFiles); $i++) 
        {
        	        	
			$sURLFile = '<a href="'.$sPath.$aFiles[$i]['file'].'" title="">'.$aFiles[$i]['file'].' ('.human_readable_size($aFiles[$i]['filesize']).')</a>';
    		$sMessage = 'Wollen Sie die Datei '.$aFiles[$i]['file'].' wirklich löschen?';
			$sURLDelete = $oControls->getDeleteLink($sMessage, array($sActionName => $sActionValue, $sActionName2 => $sActionValue2, "file_to_delete" => $aFiles[$i]['file']), 'Datei löschen', '', $area, $frame, $sess->id);
            $sHTMLOutput .= '<div style="padding: 5px;">'.$sURLFile.' '.$sURLDelete.'</div>';
    	}
    	return $sHTMLOutput;
    }else
    {
    	return '';
    }
}

?>