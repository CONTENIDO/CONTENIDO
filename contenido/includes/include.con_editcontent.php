<?php

/******************************************
* File      :   include.con_editcontent.php
* Project   :   Contenido
* Descr     :   Include for editing the
*               content in an article
*
* Author    :   Jan Lengowski
*
* Created   :   00.00.0000
* Modified  :   $Date$
*
* @internal {
*   modified 2008-06-16, H. Librenz - Hotfix: check for illegal calls added
*
*   $Id$
* }
* © four for business AG
******************************************/
if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
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
				//echo "conSaveContentEntry({$value[0]}, CMS_{$value[1]}, {$value[2]}, value)<br>\n";
			}

			conMakeArticleIndex ($idartlang, $idart);

			// restore orginal values
			$data 	= $_REQUEST['data'];
			$value	= $_REQUEST['value'];

			conGenerateCodeForArtInAllCategories ($idart);
		}
	}

	if ( $action == 10 )
	{
		header("Location: ".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php?type=$type&typenr=$typenr&client=$client&lang=$lang&idcat=$idcat&idart=$idart&idartlang=$idartlang&contenido=$contenido&lang=$lang");
	} else {

		$markSubItem = markSubMenuItem(3, true);

	$scripts .= <<<EOD

<script language="javascript">

// searches the classname in the td above or the tr above
function getCellClass(element) {

    var el = document.getElementById(element);
    var cell = el.offsetParent;

    // if there is a classname in the td return the classname
    if ( cell.className != '' ) {
        return cell.className;
    }

    // set a flag in this td
    if ( cell.id == '' ) {
       cell.id = 'yes';
       var flg = 'yes'

    } else {
       var flg = cell.id;

    }

    //else go to the tagname table above   and search dowen for the tr tags
    while (el.tagName != 'TABLE') {
          el = el.offsetParent;
    }

    var elements = el.getElementsByTagName('TR');

    //go thrue all tr tags
    for (var row in elements) {
       if (isNaN(elements[row])) {
          var cells = elements[row].getElementsByTagName('TD');
          for (var id in cells) {
             if (isNaN(cells[id])){
                // check if the flg(td cell) is in this tr    if true return the calssname
                if (cells[id].id==flg&&elements[row].className != '') {
                   return elements[row].className;
                }
             }
          }
       }
    }

    return false;
}

function setcontent(idartlang, act) {
    if (document.all) {
        document.getElementsByTagName = function (str) {
            if (str=="*")
                return document.all;
            else
                return document.all.tags(str);
        }
    }

    var a = document.getElementsByTagName("*");
    var str = '';
    var aId = '';

    // loop through all elements
    for (var i=0; i < a.length; i++) {

        aId = a[i].id;

        if (aId != '' && typeof aId == 'string') {

            var aIdPrefix = aId.substr(0,4);

            // search for the id which containes HTML
            if (aIdPrefix == 'HTML') {

                // check if its an 'contentEditable' Field
                if (a[i].isContentEditable == true) {

                    // split the idname in data
                    var data = aId.split("_");

                    // data[0] is the fieldname * needed
                    // data[1] is the idtype
                    // data[2] is the typeid * needed

                    // read out the content
                    var aContent = prepareString(a[i].innerHTML);

                    // build the string which will be send
                    str += buildDataEntry(idartlang , data[0] , data[2] , aContent);
                }
            }
        }
    }

    // set the string
    document.forms.editcontent.data.value = str + document.forms.editcontent.data.value;

    // set the action string
    if ( act != 0 ) {
        document.forms.editcontent.action = act;
    }

    // if there are 3 arguments, the className has to be seached
    if (arguments.length > 2){
       //search the class of the above element
       var classname = getCellClass(arguments[2]);

       if ( classname ) {
          document.forms.editcontent.con_class.value = classname;
       }
    }

    // submit the form
    document.forms.editcontent.submit();
}

function prepareString(aContent) {
    if ( aContent == "&nbsp;" || aContent == "" ) {
        aContent = "%$%EMPTY%$%";
    } else {
        // if there is an | in the text set a replacement chr because we use it later as isolator
        while( aContent.search(/\|/) != -1 ) {
            aContent = aContent.replace(/\|/,"%$%SEPERATOR%$%");
        }
    }

    return aContent;
}

function buildDataEntry(idartlang, type, typeid, value) {
    return idartlang +'|'+ type +'|'+ typeid +'|'+ value +'||';
}

function addDataEntry(idartlang, type, typeid, value) {
    document.forms.editcontent.data.value = (buildDataEntry(idartlang, type, typeid, prepareString(value) ) );

    setcontent(idartlang,'0');
}

</script>

EOD;

        $scripts .= '<script src="'.$cfg["path"]["contenido_fullhtml"].'external/mozile/mozileLoader.js" type="text/javascript"></script>';

        $contentform  = "<form name=\"editcontent\" method=\"post\" action=\"".$sess->url("front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&action=20")."\">\n";
        $contentform .= "<input type=\"hidden\" name=\"changeview\" value=\"edit\">\n";
        $contentform .= "<input type=\"hidden\" name=\"data\" value=\"\">\n";
        $contentform .= "<input type=\"hidden\" name=\"con_class\" value=\"\">\n";
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
                    a.idart     = '".$idart."' AND
                    a.idlang    = '".$lang."' AND
                    b.idart     = a.idart AND
                    b.idclient  = '".$client."'";

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
                        idtplcfg = '".$idtplcfg."'
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
                        a.idcat     = '".$idcat."' AND
                        a.idlang    = '".$lang."' AND
                        b.idcat     = a.idcat AND
                        b.idclient  = '".$client."'";

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
                            idtplcfg = '".$idtplcfg."'
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
				if ( $_REQUEST['cfg'] ) { exit; }
                include_once ($cfg["path"]["contenido"].$cfg["path"]["classes"]."class.notification.php");
                include_once ($cfg["path"]["contenido"].$cfg["path"]["classes"]."class.table.php");

                if ( !is_object($notification) ) {
                    $notification = new Contenido_Notification;
                }

                $sql = "SELECT title FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang = '".$idartlang."'";
                $db->query($sql);
                $db->next_record();
                $art_name = $db->f("title");

                $cat_name = "";
                conCreateLocationString($idcat, "&nbsp;/&nbsp;", $cat_name);

                $sql = "SELECT name FROM ".$cfg["tab"]["lang"]." WHERE idlang = '".$lang."'";
                $db->query($sql);
                $db->next_record();
                $lang_name = $db->f("name");

                $sql = "SELECT name FROM ".$cfg["tab"]["clients"]." WHERE idclient = '".$client."'";
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

                $sql = "SELECT * FROM ".$cfg["tab"]["code"]." WHERE idcatart='".$idcatart."' AND idlang='".$lang."'";

                $db->query($sql);

                if ($db->next_record()) {
                    $sql = "UPDATE ".$cfg["tab"]["code"]." SET code='".$code."', idlang='".$lang."', idclient='".$client."' WHERE idcatart='".$idcatart."' AND idlang='".$lang."'";
                    $db->query($sql);

                } else {
                    $sql = "INSERT INTO ".$cfg["tab"]["code"]." (idcode, idcatart, code, idlang, idclient) VALUES ('".$db->nextid($cfg["tab"]["code"])."', '".$idcatart."', '".$code."', '".$lang."', '".$client."')";
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
                    b.idtplcfg  = '".$idtplcfg."' AND
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
                    idtpl = '".$idtpl."'
                ORDER BY
                    number ASC";

        $db->query($sql);

        while ( $db->next_record() ) {
            $a_d[$db->f("number")] = $db->f("idmod");
        }


        #
        # Get code from Layout
        #
        $sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idlay = '".$idlay."'";

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

                    $sql = "SELECT * FROM ".$cfg["tab"]["mod"]." WHERE idmod='".$a_d[$value]."'";

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

                    $output = eregi_replace("(CMS_VALUE\[)([0-9]*)(\])", "", $output);

                    /* Long syntax with closing tag */
                    $code = preg_replace("/<container( +)id=\\\\\"$value\\\\\"(.*)>(.*)<\/container>/i", "CMS_CONTAINER[$value]", $code);

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
                    B.idart     = '".$idart."' AND
                    B.idlang    = '".$lang."'";

        $db->query($sql);

        while ( $db->next_record() ) {
            $a_content[$db->f("type")][$db->f("typeid")] = $db->f("value");
        }

        $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".$idart."' AND idlang='".$lang."'";

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
			echo "<textarea>".htmlspecialchars($code)."</textarea>";
      	}

        $code = str_ireplace_once("<head>", "<head>\n".'<base href="'.$cfgClient[$client]["path"]["htmlpath"].'">', $code);

        chdir($cfgClient[$client]["path"]["frontend"]);
      	eval("?>\n".$code."\n<?php\n");



    }
}
page_close();

?>
