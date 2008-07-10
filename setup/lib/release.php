<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Release toolkit
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2005-10-06
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


include_once ('../../contenido/includes/startup.php');
cInclude("classes", "xml/class.xml2array.php");
cInclude("classes", "class.csv.php");
cInclude("includes", "functions.database.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
  <title>Contenido - Release Toolkit</title>
  <style type="text/css">
     body,tr,td {
                   background-color: #ffffff;
                   scrollbar-face-color:#C6C6D5;
                   scrollbar-highlight-color:#FFFFFF;
                   scrollbar-3dlight-color:#747488;
                   scrollbar-darkshadow-color:#000000;
                   scrollbar-shadow-color:#334F77;
                   scrollbar-arrow-color:#334F77;
                   scrollbar-track-color:#C7C7D6;
		           font-family: Verdana, Arial, Helvetica, Sans-Serif;
		           font-size: 11px;
		           color: #000000;
                }
     a {
                   color: #000099;
     }
     h1 {
                   font-family: Verdana, Arial, Helvetica, Sans-Serif; 
                   font-size: 20px;
                   color: #000000;
                   margin-top: 0px;
        }
     h2 {
                   font-family: Verdana, Arial, Helvetica, Sans-Serif;
                   font-size: 15px;
                   color: #000000;
        }
     table {
                   border: none;
                   padding: 0;
                   margin: 0;
                   text-align: left;
                   width: 100%;
     }
     td {
                   vertical-align: top;
     }
     img {
                   border: 0;
     }
     pre {
                   padding: 0;
                   margin: 0;
     }
  </style>
</head>
<body>
<table>
  <tbody>
    <tr>
      <td>
        <img src="../images/logo.gif" alt="Contenido" width="200" height="30" style="padding-right: 20px;">
        <h1>Contenido - Release Toolkit</h1>
      </td>
    </tr>
  </tbody>
</table>
<br>
<?php

$cApiXml2Array = new cApiXml2Array();

$cApiXml2Array->loadData("release.xml");

$aReleaseInfo = $cApiXml2Array->getResult();

/* Extract version information */
$sTargetVersion = $aReleaseInfo["release"]["version"][0]["content"];
$sSourcePrefix =  $aReleaseInfo["release"]["sqlfiles"][0]["@sourceprefix"];
$sTargetPrefix =  $aReleaseInfo["release"]["sqlfiles"][0]["@targetprefix"];
$sLinesPerFile =  $aReleaseInfo["release"]["sqlfiles"][0]["@linesperfile"];

$db = new DB_Contenido;

echo "<h2>Execute scripts and prepare file contents</h2>\n";

foreach ($aReleaseInfo["release"]["sqlfiles"][0]["rules"] as $rules)
{
	$group = $rules["@group"];
	
	$groupfiles[$group] = array();
	echo "<br /><strong>Parsing group $group</strong><br />\n";
	
	foreach ($rules["rule"] as $rule)
	{
		$mcount = 1;
		
		$file = $rule["@file"];
		
		echo " Preparing file $file<br />\n";
		$prerun = "";
		
		if (is_array($rule["prerun"]))
		{
			foreach ($rule["prerun"] as $preruns)
			{
				$sqlchunks[$group."/".$file.$mcount.".sql"][] = $preruns["content"]; 
				$groupfiles[$group][] = $group."/".$file.$mcount.".sql";
			}
		}
		
		$source = "";
				
		if (is_array($rule["source"]))
		{
			foreach ($rule["source"] as $sources)
			{
				$source = $sources["content"];
				
				$source = str_replace($sTargetPrefix, $sSourcePrefix, $source);
				
				$db->query($source);
				echo "  Executing <pre>$source</pre><br />\n";
				
				$sqlcount = 0;
				while ($db->next_record())
				{
					/* Extract the table name */
					$sTableName = mysql_field_table($db->Query_ID, '0');
					$sTableName = str_replace($sSourcePrefix."_", $sTargetPrefix."_", $sTableName);
					
					$targetSQL = "INSERT INTO %s VALUES(%s);";
					
					$aInsert = array();
					for ($i=0;$i<$db->num_fields();$i++)
					{
						$data = $db->f($i);
						$rootpath = str_replace("/contenido/", "/", $cfg['path']['contenido']);
						$webpath = str_replace("/contenido/", "/", $cfg['path']['contenido_fullhtml']);
						
						$data = str_replace($rootpath, '<!--{contenido_root}-->/', $data);
						$data = str_replace($webpath, '<!--{contenido_web}-->/', $data);
						
						$data = str_replace("\\", "\\\\", $data);
						$data = str_replace("\n", "\\n", $data);
						$data = str_replace("\r", "\\r", $data);
						$data = str_replace("'", "''", $data);
						$aInsert[] = "'".$data."'";
					}
					
					$sqlchunks[$group."/".$file.$mcount.".sql"][] = sprintf($targetSQL, $sTableName, implode(", ", $aInsert));
					
					$groupfiles[$group][] = $group."/".$file.$mcount.".sql";
					$sqlcount++;
					
					if ($sqlcount > $sLinesPerFile)
					{
						$sqlcount = 0;
						$mcount++;
					}
				} 
			}
		}
	}
}

echo "<h2>Writing data files</h2>\n";

foreach ($sqlchunks as $file => $sqlchunk)
{
	echo "Writing ".$cfg["path"]["contenido"]."../setup/data/".$file."<br />\n";
	@mkdir(dirname($cfg["path"]["contenido"]."../setup/data/".$file));
	file_put_contents($cfg["path"]["contenido"]."../setup/data/".$file, implode(PHP_EOL, $sqlchunk));
}

echo "<h2>Writing setup variants files</h2>\n";

foreach ($groupfiles as $group => $files)
{
	$filename     = $group.".txt";
	$filecontents = implode(PHP_EOL, array_unique($files));
	
	file_put_contents($cfg["path"]["contenido"]."../setup/data/".$filename, $filecontents);
	
	echo "Writing ".$cfg["path"]["contenido"]."../setup/data/".$filename."<br \>\n";
}

echo "<h2>Exporting table structures</h2>\n";

$dbexport = new DB_Contenido;
$rawtext = true;

foreach ($cfg["tab"] as $key => $value)
{
	echo "Exporting table $value<br />\n";
	$tArray[$value] = dbDumpStructure($dbexport, $value, $rawtext);
}
		
$csv = new CSV;
$row = 1;		
ksort($tArray);
		
foreach ($tArray as $table)
{
	foreach ($table as $field)
	{
		$row++;
		$cell = 1;
		foreach ($field as $entry)
		{
			$cell++;
			$csv->setCell($row, $cell, $entry);
		}
	}	
}
		
file_put_contents($cfg["path"]["contenido"]."../setup/data/tables.txt", $csv->make());
?>
<h2>Finished</h2>
</body>
</html>
