<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Include for editing the content in an article
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.3
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2003
 *   modified 2008-06-16, Holger Librenz, Hotfix: check for illegal calls added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-10-29, Murat Purc, replaced deprecated functions (PHP 5.3 ready) and some formatting
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.con_editcontent.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$edit 		= "true";

$db2 		= new DB_Contenido;
$scripts	= "";

if ( isset($idcat) )
{
	if( $action == 20 || $action == 10 )
	{
		if( $data != "" )
		{
			$data = explode("||", substr($data, 0, -2));

			foreach($data as $value)
			{
				$value = explode("|", $value);

				if ( $value[3] == "%$%EMPTY%$%" ) {
					$value[3] = "";
				} else {
					$value[3] = str_replace("%$%SEPERATOR%$%", "|", $value[3]);
				}

				conSaveContentEntry($value[0], "CMS_".$value[1], $value[2], $value[3]);
			}

			conMakeArticleIndex ($idartlang, $idart);

			// restore orginal values
			$data 	= $_REQUEST['data'];
			$value	= $_REQUEST['value'];
		}
		
		conGenerateCodeForArtInAllCategories ($idart);
	}

	if ( $action == 10 )
	{
		header("Location: ".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php?type=$type&typenr=$typenr&client=$client&lang=$lang&idcat=$idcat&idart=$idart&idartlang=$idartlang&contenido=$contenido&lang=$lang");
	} else {

		$markSubItem = markSubMenuItem(3, true);

    //Include tiny class
    include ($cfg["path"]["contenido"] . 'external/wysiwyg/tinymce3/editorclass.php');
    $oEditor = new cTinyMCEEditor ('', '');
    $oEditor->setToolbar('inline_edit');
    
    //Get configuration for popup und inline tiny
    $sConfigInlineEdit = $oEditor->getConfigInlineEdit(); 
    $sConfigFullscreen = $oEditor->getConfigFullscreen();        
    
    //Include tiny mce and con_tiny script for insight_editing    
    $scripts .= "\n".'<script src="'.$cfg["path"]["contenido_fullhtml"].'scripts/jquery/jquery.js" type="text/javascript"></script>';
    $scripts .= "\n".'<script src="'.$cfg["path"]["contenido_fullhtml"].'scripts/con_tiny.js" type="text/javascript"></script>';
    $scripts .= "\n<!-- tinyMCE -->\n".'<script language="javascript" type="text/javascript" src="'.$cfg["path"]["contenido_fullhtml"].'external/wysiwyg/tinymce3/jscripts/tiny_mce/tiny_mce.js"></script>';
    
    //Script template for insight editing
	$scripts .= <<<EOD
<style type="text/css">
	.defaultSkin table.mceLayout {position: absolute; z-index: 10000;}
	.defaultSkin #mce_fullscreen_tbl {z-index: 20000;}
	.defaultSkin .mcePlaceHolder {position: absolute; z-index: 10000;}
</style>
	
	
<script language="javascript">

tinymce.create('tinymce.plugins.ClosePlugin', {
	createControl: function(n, cm) {
		switch (n) {
			case 'close':
				var c = cm.createMenuButton('close', {
					title : '{CLOSE}',
					image : '{CON_PATH}images/back.gif',
					icons : false,
					onclick : function(ed) {
			           closeTiny();
			        }
				});

				// Return the new menu button instance
				return c;
				
			case 'save':
				var c = cm.createMenuButton('save', {
					title : '{SAVE}',
					image : '{CON_PATH}images/save.gif',
					icons : false,
					onclick : function(ed) {
			           setcontent(iIdartlang, '0');
			        }
				});

				// Return the new menu button instance
				return c;
		}

		return null;
	}
});

// Register plugin with a short name
tinymce.PluginManager.add('close', tinymce.plugins.ClosePlugin);

var active_id = null;  //id of div on which tiny is active
var active_object = null;  //onject of div on which tiny is active
var aEditdata = new Object();  //global array which stores edited content
var aEditdataOrig = new Object();  //global array which stored original content (Importent for decision if content has changed)
var bCheckLeave = true;  //globak var which defines if user is asked to store changes

//Global vars for contenido popup filebrowser
var fb_fieldname;
var fb_handle;
var fb_intervalhandle;
var fb_win;

//Configuration of tiny, when tiny is opened set event which stores original 
//content to global var aEditdataOrig
var tinymceConfigs = {
    {TINY_OPTIONS},
    fullscreen_settings : {
        {TINY_FULLSCREEN}
    },
    'setup' : function(ed) {
      ed.onSetContent.add(function(ed, o) {
            updateContent(ed.getContent());
      })}
};
tinyMCE.settings = tinymceConfigs;

//add tiny to elements which contains classname contentEditable
//tiny toggles on click 
$(document).ready( function(){
   $('div[contenteditable=true]').each( function(){
	  $(this).attr('contentEditable', 'false'); //remove coneditable tags in order to disable special firefox behaviour
      $(this).bind( "click", function(){
         {USE_TINY}
      });
   });
});

//activate save confirmation on page leave
if (document.all) {
	window.onunload = leave_check;
} else {
	window.onbeforeunload = leave_check;
}

var file_url = "{FILE}"; //Global var which contains url to contenido image browser
var image_url = "{IMAGE}"; //Global var which contains url to contenido file browser
var flash_url = "{FLASH}"; //Global var which contains url to contenido flash browser
var media_url = "{MEDIA}"; //Global var which contains url to contenido media browser
var frontend_path = "{FRONTEND}";

var iIdartlang = '{IDARTLANG}'; //Idartlang which is currently edited
var sQuestion = '{QUESTION}'; //Translation of save confirmation

</script>

EOD;

        //Replace vars in Script
        $oScriptTpl = new Template();
        
        //Set urls to file browsers
        $oScriptTpl->set('s', 'IMAGE', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser');
        $oScriptTpl->set('s', 'FILE', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=filebrowser');
        $oScriptTpl->set('s', 'FLASH', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser');
        $oScriptTpl->set('s', 'MEDIA', $cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser');
        $oScriptTpl->set('s', 'FRONTEND', $cfgClient[$client]["path"]["htmlpath"]);
		
        //Add tiny options and fill function leave_check()
        $oScriptTpl->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
        $oScriptTpl->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
        $oScriptTpl->set('s', 'IDARTLANG', $idartlang);
		$oScriptTpl->set('s', 'CON_PATH', $cfg["path"]["contenido_fullhtml"]);
		$oScriptTpl->set('s', 'CLOSE', i18n("Close editor"));
		$oScriptTpl->set('s', 'SAVE', i18n("Close editor and save changes"));
        $oScriptTpl->set('s', 'QUESTION', i18n("Do you want to save changes?"));
		
		if (getEffectiveSetting('system', 'insight_editing_activated', 'true') == 'false') {
			$oScriptTpl->set('s', 'USE_TINY', '');
		} else {
			$oScriptTpl->set('s', 'USE_TINY', 'swapTiny(this);');
		}
        
        $scripts = $oScriptTpl->generate($scripts, 1);
        
        $contentform  = "<form name=\"editcontent\" method=\"post\" action=\"".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&action=20&client=$client")."\">\n";
        $contentform .= "<input type=\"hidden\" name=\"changeview\" value=\"edit\">\n";
        $contentform .= "<input type=\"hidden\" name=\"data\" value=\"\">\n";
        $contentform .= "</form>";

        #
        # extract IDCATART
        #
        $sql = "SELECT
                    idcatart
                FROM
                    ".$cfg["tab"]["cat_art"]."
                WHERE
                    idcat = '".$idcat."' AND
                    idart = '".$idart."'";

        $db->query($sql);
        $db->next_record();

        $idcatart = $db->f("idcatart");

        #
        # Article is not configured,
        # if not check if the category
        # is configured. It neither the
        # article or the category is
        # configured, no code will be
        # created and an error occurs.
        #

        $sql = "SELECT
                    a.idtplcfg AS idtplcfg
                FROM
                    ".$cfg["tab"]["art_lang"]." AS a,
                    ".$cfg["tab"]["art"]." AS b
                WHERE
                    a.idart     = '".Contenido_Security::toInteger($idart)."' AND
                    a.idlang    = '".Contenido_Security::toInteger($lang)."' AND
                    b.idart     = a.idart AND
                    b.idclient  = '".Contenido_Security::toInteger($client)."'";

        $db->query($sql);
        $db->next_record();

        if ( $db->f("idtplcfg") != 0 ) {

            #
            # Article is configured
            #
            $idtplcfg = $db->f("idtplcfg");

            $a_c = array();

            $sql2 = "SELECT
                        *
                     FROM
                        ".$cfg["tab"]["container_conf"]."
                     WHERE
                        idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'
                     ORDER BY
                        number ASC";

            $db2->query($sql2);

            while ( $db2->next_record() ) {
                $a_c[$db2->f("number")] = $db2->f("container");

            }

        } else {

            #
            # Check whether category is
            # configured.
            #
            $sql = "SELECT
                        a.idtplcfg AS idtplcfg
                    FROM
                        ".$cfg["tab"]["cat_lang"]." AS a,
                        ".$cfg["tab"]["cat"]." AS b
                    WHERE
                        a.idcat     = '".Contenido_Security::toInteger($idcat)."' AND
                        a.idlang    = '".Contenido_Security::toInteger($lang)."' AND
                        b.idcat     = a.idcat AND
                        b.idclient  = '".Contenido_Security::toInteger($client)."'";

            $db->query($sql);
            $db->next_record();

            if ( $db->f("idtplcfg") != 0 ) {

                #
                # Category is configured,
                # extract varstring
                #
                $idtplcfg = $db->f("idtplcfg");

                $a_c = array();

                $sql2 = "SELECT
                            *
                         FROM
                            ".$cfg["tab"]["container_conf"]."
                         WHERE
                            idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'
                         ORDER BY
                            number ASC";

                $db2->query($sql2);

                while ( $db2->next_record() ) {
                    $a_c[$db2->f("number")] = $db2->f("container");

                }

            } else {

                #
                # Article nor Category
                # is configured. Creation of
                # Code is not possible. Write
                # Errormsg to DB.
                #
                include_once ($cfg["path"]["contenido"].$cfg["path"]["classes"]."class.notification.php");
                include_once ($cfg["path"]["contenido"].$cfg["path"]["classes"]."class.table.php");

                if ( !is_object($notification) ) {
                    $notification = new Contenido_Notification;
                }

                $sql = "SELECT title FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang = '".Contenido_Security::toInteger($idartlang)."'";
                $db->query($sql);
                $db->next_record();
                $art_name = $db->f("title");

                $cat_name = "";
                conCreateLocationString($idcat, "&nbsp;/&nbsp;", $cat_name);

                $sql = "SELECT name FROM ".$cfg["tab"]["lang"]." WHERE idlang = '".Contenido_Security::toInteger($lang)."'";
                $db->query($sql);
                $db->next_record();
                $lang_name = $db->f("name");

                $sql = "SELECT name FROM ".$cfg["tab"]["clients"]." WHERE idclient = '".Contenido_Security::toInteger($client)."'";
                $db->query($sql);
                $db->next_record();
                $client_name = $db->f("name");

                $noti_html = '<table cellspacing="0" cellpadding="2" border="0">

                                <tr class="text_medium">
                                    <td colspan="2">
                                        <b>'.i18n("No template assigned to the category<br>and/or the article").'</b><br><br>
                                        '.i18n("The code for the following article<br>couldnt be generated:").'
                                        <br><br>
                                    </td>
                                </tr>

                                <tr class="text_medium">
                                    <td >'.i18n("Article").':</td>
                                    <td><b>'.$art_name.'</b></td>
                                </tr>

                                <tr class="text_medium">
                                    <td >'.i18n("Category").':</td>
                                    <td><b>'.$cat_name.'</b></td>
                                </tr>

                                <tr class="text_medium">
                                    <td>'.i18n("Language").':</td>
                                    <td><b>'.$lang_name.'</b></td>
                                </tr>

                                <tr class="text_medium">
                                    <td>'.i18n("Client").':</td>
                                    <td><b>'.$client_name.'</b></td>
                                </tr>

                                <tr>
                                    <td colspan="2">&nbsp;</td>
                                </tr>

                              </table>';

                $code = '
                        <html>
                            <head>
                                <title>Error</title>
                                <link rel="stylesheet" type="text/css" href="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["styles"].'contenido.css"></link>
                            </head>
                            <body style="margin: 10px">'.$notification->returnNotification("error", $noti_html).'</body>
                        </html>';

                $sql = "SELECT * FROM ".$cfg["tab"]["code"]." WHERE idcatart='".Contenido_Security::toInteger($idcatart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                $db->query($sql);

                if ($db->next_record()) {
                    $sql = "UPDATE ".$cfg["tab"]["code"]." SET code='".Contenido_Security::escapeDB($code, $db)."', idlang='".Contenido_Security::toInteger($lang)."', idclient='".Contenido_Security::toInteger($client)."'
                            WHERE idcatart='".Contenido_Security::toInteger($idcatart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                    $db->query($sql);
                } else {
                    $sql = "INSERT INTO ".$cfg["tab"]["code"]." (idcode, idcatart, code, idlang, idclient) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["code"]))."', '".Contenido_Security::toInteger($idcatart)."',
                            '".Contenido_Security::escapeDB($code, $db)."', '".Contenido_Security::toInteger($lang)."', '".Contenido_Security::toInteger($client)."')";
                    $db->query($sql);
                }

                echo $code;

            }

        }

        #
        # Get IDLAY and IDMOD array
        #
        $sql = "SELECT
                    a.idlay AS idlay,
                    a.idtpl AS idtpl
                FROM
                    ".$cfg["tab"]["tpl"]." AS a,
                    ".$cfg["tab"]["tpl_conf"]." AS b
                WHERE
                    b.idtplcfg  = '".Contenido_Security::toInteger($idtplcfg)."' AND
                    b.idtpl     = a.idtpl";

        $db->query($sql);
        $db->next_record();

        $idlay = $db->f("idlay");
        $idtpl = $db->f("idtpl");

        #
        # List of used modules
        #
        $sql = "SELECT
                    number,
                    idmod
                FROM
                    ".$cfg["tab"]["container"]."
                WHERE
                    idtpl = '".Contenido_Security::toInteger($idtpl)."'
                ORDER BY
                    number ASC";

        $db->query($sql);

        while ( $db->next_record() ) {
            $a_d[$db->f("number")] = $db->f("idmod");
        }

        #
        # Get code from Layout
        #
        $sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idlay = '".Contenido_Security::toInteger($idlay)."'";

        $db->query($sql);
        $db->next_record();

        $code = $db->f("code");
        $code = AddSlashes($code);

        #
        # Create code for all containers
        #
        if ($idlay) {
				tplPreparseLayout($idlay);
                $tmp_returnstring = tplBrowseLayoutForContainers($idlay);

                $a_container = explode("&", $tmp_returnstring);

                foreach ($a_container as $key=>$value) {

					$CiCMS_VALUE = "";

                    $sql = "SELECT * FROM ".$cfg["tab"]["mod"]." WHERE idmod='".Contenido_Security::toInteger($a_d[$value])."'";

                    $db->query($sql);
                    $db->next_record();

					if (is_numeric($a_d[$value]))
					{
						$thisModule = '<?php $cCurrentModule = '.((int)$a_d[$value]).'; ?>';
						$thisContainer = '<?php $cCurrentContainer = '.((int)$value).'; ?>';
					}

                    $output = $thisModule . $thisContainer . $db->f("output");
                    $output = AddSlashes($output);

                    $template = $db->f("template");

					if (array_key_exists($value, $a_c))
					{
						$a_c[$value] = preg_replace("/(&\$)/","", $a_c[$value]);
	                    $tmp1 = preg_split("/&/", $a_c[$value]);
					} else {
						$tmp1 = array();
					}

                    $varstring = array();

                    foreach ($tmp1 as $key1=>$value1) {
                            $tmp2 = explode("=", $value1);
                            foreach ($tmp2 as $key2 => $value2) {
                                    $varstring["$tmp2[0]"] = $tmp2[1];
                            }
                    }

                   	$CiCMS_Var = '$C'.$value.'CMS_VALUE';
                    $CiCMS_VALUE = '';

                    foreach ($varstring as $key3=>$value3){
                      $tmp = urldecode($value3);
                      $tmp = str_replace("\'", "'", $tmp);
                      $CiCMS_VALUE .= $CiCMS_Var.'['.$key3.']="'.$tmp.'"; ';
                      $output = str_replace("\$CMS_VALUE[$key3]", $tmp, $output);
                      $output = str_replace("CMS_VALUE[$key3]", $tmp, $output);
                    }

                    $output = str_replace("CMS_VALUE", $CiCMS_Var, $output);
                    $output = str_replace("\$".$CiCMS_Var, $CiCMS_Var, $output);

                    $output = preg_replace('/(CMS_VALUE\[)([0-9]*)(\])/i', '', $output);

                    /* Long syntax with closing tag */
                    $code = preg_replace("/<container( +)id=\\\\\"$value\\\\\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$value]", $code);

                    /* Short syntax */
                    $code = preg_replace("/<container( +)id=\\\\\"$value\\\\\"(.*)\/>/i", "CMS_CONTAINER[$value]", $code);

                    $code = str_ireplace("CMS_CONTAINER[$value]", "<?php $CiCMS_VALUE ?>\r\n".$output, $code);

                }
        }

        #
        # Find out what kind of CMS_... Vars are in use
        #
        $sql = "SELECT
                    *
                FROM
                    ".$cfg["tab"]["content"]." AS A,
                    ".$cfg["tab"]["art_lang"]." AS B,
                    ".$cfg["tab"]["type"]." AS C
                WHERE
                    A.idtype    = C.idtype AND
                    A.idartlang = B.idartlang AND
                    B.idart     = '".Contenido_Security::toInteger($idart)."' AND
                    B.idlang    = '".Contenido_Security::toInteger($lang)."'";

        $db->query($sql);

        while ( $db->next_record() ) {
            $a_content[$db->f("type")][$db->f("typeid")] = $db->f("value");
        }

        $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";

        $db->query($sql);
        $db->next_record();

        $idartlang = $db->f("idartlang");

        #
        # Replace all CMS_TAGS[]
        #
        $sql = "SELECT idtype, type, code FROM ".$cfg["tab"]["type"];

        $db->query($sql);

        while ( $db->next_record() )
        {

            $tmp = preg_match_all("/(".$db->f("type")."\[+\d+\])/i", $code, $match);
            $a_[strtolower($db->f("type"))] = $match[0];
            $success = array_walk($a_[strtolower($db->f("type"))], 'extractNumber');

    		$search = array();
    		$replacements = array();


            foreach ($a_[strtolower($db->f("type"))] as $val)
            {
                eval ($db->f("code"));

                $search[$val] = $db->f("type") ."[$val]";
                $replacements[$val] = $tmp;
            }

            $code  = str_ireplace($search, $replacements, $code);
		}

		unset($tmp);

        /* output the code */
        $code = stripslashes($code);
        $code = str_ireplace_once("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
        $code = str_ireplace_once_reverse("</body>", "$contentform</body>", $code);

		if ($cfg["debug"]["codeoutput"])
      	{
			echo "<textarea>".conHtmlSpecialChars($code)."</textarea>";
      	}

        $code = str_ireplace_once("<head>", "<head>\n".'<base href="'.$cfgClient[$client]["path"]["htmlpath"].'">', $code);

        chdir($cfgClient[$client]["path"]["frontend"]);
      	eval("?>\n".$code."\n<?php\n");



    }
}
page_close();

?>