<?php

/**
 * HTML Template Dialog
 *
 * Dialog to choose a HTML template for use
 * in SPAW richtext editor 
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
 
 if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}
 
$contenido_path = "../../../../";

include ($contenido_path . "includes/startup.php"); 
include ($cfg["path"]["wysiwyg"].'config/spaw_control.config.php'); 

$theme = empty($_POST['theme']) ? (empty($_GET['theme']) ? $spaw_default_theme:$_GET['theme']):$_POST['theme'];
$theme_path = $spaw_dir.'lib/themes/'.$theme.'/';

$l = new SPAW_Lang(empty($_POST['lang'])?$_GET['lang']:$_POST['lang']);
$l->setBlock('image_insert');

function getFileContents($path)
{
	$lines = file($path);

	foreach($lines as $key => $value)
    {
        $lines[$key] = str_replace("\n", "", $value);
        $lines[$key] = str_replace("'", "\'", $value);
        $lines[$key] = trim($value);
    }

    $content = implode("", $lines);
	return $content;
}

$handle = opendir($cfgClient[$client]['path']['frontend'] . 'templates/');   

while ($file = readdir ($handle)) 
{
	//echo $file.": ".strrpos($file, 'stpl_')."<br>";
    if ($file != '..' && $file != '.' && !is_dir($file) && preg_match('!(stpl_)!iu', $file))
    {
    	$html_templates[] = array($file, getFileContents($cfgClient[$client]['path']['frontend'] . 'templates/' . $file));
    }
}

$html = '<select name="tplid" onchange="changeTemplate(this)" multiple="multiple" style="width: 125px; height: 310px">';
$js = "templateContents = [];\n";

if (is_array($html_templates))
{
    foreach ($html_templates as $id => $contents)
    {  
        $html .= '<option value="'.$id.'">'.$contents[0].'</option>';             
        $js .= 'templateContents['.$id.'] = \''. $contents[1] .'\';'."\n";
    }
}

$html .= '</select>';

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
	<title>HTML Templates</title>
    <style type="text/css">
    
        html, body, button, div, input, select, table {  font-family: MS Shell Dlg; font-size: 8pt; }
        body { margin: 0px; background-color: #E2E2E2; color: windowtext; }
        .button {cursor: hand}
    </style>
	<script type="text/javascript">
        
        <?php echo $js ?>
        
        function templateCancel()
        {
            window.returnValue = "";
            window.close();
        }
        
        function selectTemplate()
        {
            window.returnValue = document.getElementById("preview").innerHTML;
            window.close();
        }
        
        function changeTemplate(obj)
        {         
            var preview = document.getElementById("preview");   
            preview.innerHTML = templateContents[obj.value];
            
            tables = preview.getElementsByTagName("TABLE");
            
            for (i=0; i<tables.length; i++)
            {
                tables[i].runtimeStyle.borderWidth = "1px";
                tables[i].runtimeStyle.borderStyle = "dashed";
                tables[i].runtimeStyle.borderColor = "#aaaaaa";
            }          
        }

		window.onunload = function() {
			if (typeof window.returnValue == "undefined") { 
				window.returnValue = "";
			}
		}
        
	</script>
	<base href="<?php echo $cfgClient[$client]["htmlpath"]["frontend"]; ?>"/>
</head>
<body>  
	
    <form name="selecttemplate" method="post" action="selecttemplate.php" target="_self">
        <input type="hidden" name="theme" value="<?php echo $theme?>">
        <input type="hidden" name="lang" value="<?php echo $l->lang?>">
        <input type="hidden" name="belang" value="<?php echo $belang?>">
        <input type="hidden" name="client" value="<?php echo $client?>">

        <table cellpadding="0" cellspacing="5" border="0" width="100%">
		<tr>
        	<td width="160" valign="top">
        		<fieldset>
					<legend>Browser</legend>
					
					<table cellpadding="0" cellspacing="3" border="0">
        				<tr>
            				<td>
            				    <div style="height: 310px">
                                    <?php echo $html ?>
            				    </div>
            				</td>
            			</tr>
            		</table>
					
        		</fieldset>
        	</td>
        	<td width="240" valign="top">
            	<fieldset>
    				<legend><?php echo $l->m('preview')?></legend>    
            		<table cellpadding="0" cellspacing="3" border="0">
        				<tr>
            				<td>
                                <div id="preview" style="overflow:scroll; background-color:#ffffff; width:430; height:310; padding: 10px"></div>
                            </td>
            			</tr>	
        			</table>
            	</fieldset>      
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table width="100%" cellpadding="3" cellspacing="0" border="0">
                	<tr>
                		<td align="right" valign="middle">
                		    
                		    <img class="button" onclick="templateCancel()" src="<?php echo $cfg['path']['contenido_fullhtml'] ?>images/but_cancel.gif" width="20" height="20">&nbsp;&nbsp;
                  		    <img class="button" onclick="selectTemplate()" src="<?php echo $cfg['path']['contenido_fullhtml'] ?>images/but_ok.gif" width="20" height="20">
                		    
                		</td>
                	</tr>
                </table>    
            </td>
        </tr>
		</table>
    </form>
</body>
</html>