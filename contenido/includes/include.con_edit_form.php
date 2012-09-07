<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Form for editing the article properties
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.5.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-21
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-08-29, Murat Purc, add handling of urlname
 *   modified 2008-09-11, Andreas Lindner, added decoding of text and cat names
 *   					  with unFilter function
 *
 *   $Id: include.con_edit_form.php 843 2008-09-24 10:41:00Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("includes", "functions.str.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "contenido/class.client.php");
cInclude("classes", "class.security.php");
cInclude("includes", "functions.pathresolver.php");

$tpl->reset();

if ($action == "remove_assignments")
{
	$sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idcat != '".Contenido_Security::toInteger($idcat)."'";
	$db->query($sql);
}
if ($action == "con_newart" && $newart != true)
{
	// nothing to be done here ?!
}
else {
	$disabled = "";

	if ($perm->have_perm_area_action($area, "con_edit") ||
	$perm->have_perm_area_action_item($area,"con_edit", $idcat)) {

		$sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idcat = '".Contenido_Security::toInteger($idcat)."'";
		$db->query($sql);
		$db->next_record();

		if ($cfg["is_start_compatible"] == true)
		{
			$tmp_is_start = $db->f("is_start");
		}
		 
		$tmp_cat_art = $db->f("idcatart");

		$sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";

		$db->query($sql);
		$db->next_record();

		if ($cfg["is_start_compatible"] == false)
		{
			$tmp_is_start = isStartArticle($db->f("idartlang"), $idcat, $lang);
		}

		if ( $db->f("created") ) {

			//****************** this art was edited before ********************
			$tmp_firstedit    = 0;
			$tmp_idartlang    = $db->f("idartlang");
			$tmp_page_title   = Contenido_Security::unFilter(stripslashes($db->f("pagetitle")));
			$tmp_idlang       = $db->f("idlang");
			$tmp_title        = Contenido_Security::unFilter($db->f("title"));
            $tmp_urlname      = Contenido_Security::unFilter($db->f("urlname"));      // plugin Advanced Mod Rewrite - edit by stese            
			$tmp_artspec	  = $db->f("artspec");
			$tmp_summary      = Contenido_Security::unFilter($db->f("summary"));
			$tmp_created      = $db->f("created");
			$tmp_lastmodified = $db->f("lastmodified");
			$tmp_author       = $db->f("author");
			$tmp_modifiedby	  = $db->f("modifiedby");
			$tmp_online       = $db->f("online");
			$tmp_published 	  = $db->f("published");
			$tmp_publishedby  = $db->f("publishedby");
			$tmp_datestart    = $db->f("datestart");
			$tmp_dateend      = $db->f("dateend");
			$tmp_sort         = $db->f("artsort");
			$tmp_movetocat    = $db->f("time_move_cat");
			$tmp_targetcat    = $db->f("time_target_cat");
			$tmp_onlineaftermove = $db->f("time_online_move");
			$tmp_usetimemgmt = $db->f("timemgmt");
			$tmp_locked = $db->f("locked");

			$tmp_redirect_checked  = ($db->f("redirect") == '1') ? 'checked' : '';
			$tmp_redirect_url           = ($db->f("redirect_url") != '0') ? $db->f("redirect_url") : "http://";
			$tmp_external_redirect_checked = ($db->f("external_redirect") == '1') ? 'checked' : '';

			$idtplinput          = $db->f("idtplinput");

			if ($tmp_modifiedby == "")
			{
				$tmp_modifiedby = $tmp_author;
			}

			$col = new InUseCollection;

			/* Remove all own marks */
			$col->removeSessionMarks($sess->id);

			if (($obj = $col->checkMark("article", $tmp_idartlang)) === false)
			{
				$col->markInUse("article", $tmp_idartlang, $sess->id, $auth->auth["uid"]);
				$inUse = false;
				$disabled = "";
			} else {
				 
				$vuser = new User;
				$vuser->loadUserByUserID($obj->get("userid"));
				$inUseUser = $vuser->getField("username");
				$inUseUserRealName = $vuser->getField("realname");
				 
				$message = sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName);
				$notification->displayNotification("warning", $message);
				$inUse = true;
				$disabled = 'disabled="disabled"';
			}

			if ($tmp_locked == 1)
			{
				$inUse = true;
				$disabled = 'disabled="disabled"';
			}

		} else {

			//***************** this art is edited the first time *************

			if (!$idart) $tmp_firstedit = 1; //**** is needed when input is written to db (update or insert)

			$tmp_idartlang      = 0;
			$tmp_idlang         = $lang;
			$tmp_page_title     = stripslashes($db->f("pagetitle"));
			$tmp_title          = "";
            $tmp_urlname        = "";   // plugin Advanced Mod Rewrite - edit by stese            
			$tmp_artspec	 	= "";
			$tmp_summary        = "";
			$tmp_created        = date("Y-m-d H:i:s");
			$tmp_lastmodified   = date("Y-m-d H:i:s");
			$tmp_published   	= date("Y-m-d H:i:s");
			$tmp_publishedby   = "";
			$tmp_author         = "";
			$tmp_online         = "0";
			$tmp_datestart      = "0000-00-00 00:00:00";
			$tmp_dateend        = "0000-00-00 00:00:00";
			$tmp_keyart         = "";
			$tmp_keyautoart     = "";
			$tmp_sort           = "";

			if (!strHasStartArticle($idcat, $lang))
			{
				$tmp_is_start = 1;
			}

			$tmp_redirect_checked  = '';
			$tmp_redirect_url           = "http://";
			$tmp_external_redirect = '';

		}

		$dateformat = getEffectiveSetting("backend", "timeformat", "Y-m-d H:i:s");
		 
		$tmp2_created = date($dateformat,strtotime($tmp_created));
		$tmp2_lastmodified = date($dateformat,strtotime($tmp_lastmodified));
		$tmp2_published = date($dateformat,strtotime($tmp_published));

		$tpl->set('s', 'ACTION', $sess->url("main.php?area=$area&frame=$frame&action=con_saveart") );
		$tpl->set('s', 'HIDDENSESSION', $sess->hidden_session(true));
		$tpl->set('s', 'TMP_FIRSTEDIT', $tmp_firstedit);
		$tpl->set('s', 'IDART', $idart);
		$tpl->set('s', 'SID', $sess->id);
		$tpl->set('s', 'IDCAT', $idcat);
		$tpl->set('s', 'IDARTLANG', $tmp_idartlang );

		$hiddenfields = '<input type="hidden" name="idcat" value="'.$idcat.'">
                         <input type="hidden" name="idart" value="'.$idart.'">
                         <input type="hidden" name="send" value="1">';

		$tpl->set('s', 'HIDDENFIELDS', $hiddenfields);

		// Show path of selected category to user		
		$catString = '';
		prCreateURLNameLocationString($idcat, '/', $catString);
		$tpl->set('s', 'CATEGORY', $catString.'/'.htmlspecialchars($tmp_title));
		
		/* Title */
		$tpl->set('s', 'TITEL', i18n("Title"));

        // plugin Advanced Mod Rewrite - edit by stese
        $tpl->set('s', 'URLNAME', i18n("Alias"));
        // end plugin Advanced Mod Rewrite

		$arrArtSpecs = getArtSpec();

		$tmp_inputArtSort = "<select $disabled name=\"artspec\" style=\"width:400px;\" class=\"text_medium\">";
		$iAvariableSpec = 0;
        foreach ($arrArtSpecs as $id => $value)
	    {
            if ($arrArtSpecs[$id]['online'] == 1) {
                if (($arrArtSpecs[$id]['default'] == 1) && (strlen($tmp_artspec) == 0 || $tmp_artspec == 0))
                {
                    $tmp_inputArtSort .= "<option value=\"$id\" selected>".urldecode($arrArtSpecs[$id]['artspec'])."</option>";
                } elseif ($id == $tmp_artspec)
                {
                    $tmp_inputArtSort .= "<option value=\"$id\" selected>".urldecode($arrArtSpecs[$id]['artspec'])."</option>";
                } else
                {
                    $tmp_inputArtSort .= "<option value=\"$id\">".ucfirst($arrArtSpecs[$id]['artspec'])."</option>";
                }
                $iAvariableSpec++;
            }
        }
        $tmp_inputArtSort .= "</select>";
        
        if ($iAvariableSpec == 0)
		{
			$tmp_inputArtSort = i18n("No article specifications found!");
		}

		$tpl->set('s', 'ARTIKELART', i18n("Article specification"));
		$tpl->set('s', 'ARTIKELARTSELECT', $tmp_inputArtSort);

		$tpl->set('s', 'TITEL-FIELD', '<input '.$disabled.' style="width:400px;" type="text" class="text_medium" name="title" value="'.htmlspecialchars($tmp_title).'">');

        // plugin Advanced Mod Rewrite - edit by stese
        $tpl->set('s', 'URLNAME-FIELD', '<input '.$disabled.' style="width:400px;" type="text" class="text_medium" name="urlname" value="'.htmlspecialchars($tmp_urlname).'">');
        // end plugin Advanced Mod Rewrite

		$tpl->set('s', 'ARTIKELID', "idart");
		$tpl->set('s', 'ARTID', $idart);

		$tpl->set('s', 'DIRECTLINKTEXT', i18n("Articlelink"));

		$select = new cHTMLSelectElement("directlink");
		$select->setEvent("change", "document.getElementById('linkhint').value = this.form.directlink.options[this.form.directlink.options.selectedIndex].value;");

		$baselink = $cfgClient[$client]["path"]["htmlpath"]."front_content.php?idart=$idart";

		$option[0] = new cHTMLOptionElement( i18n("Select an entry to display link"), "");
		$option[1] = new cHTMLOptionElement( i18n("Article only"), $baselink);
		$option[2] = new cHTMLOptionElement( i18n("Article with Category"), $baselink."&idcat=$idcat");
		$option[3] = new cHTMLOptionElement( i18n("Article with Category and Language"), $baselink."&idcat=$idcat&lang=$lang");
		$option[4] = new cHTMLOptionElement( i18n("Article with Language"), $baselink."&lang=$lang");

		$select->addOptionElement(0, $option[0]);
		$select->addOptionElement(1, $option[1]);
		$select->addOptionElement(2, $option[2]);
		$select->addOptionElement(3, $option[3]);
		$select->addOptionElement(4, $option[4]);

		$tpl->set('s', 'DIRECTLINK', $select->render().'<br><br><input style="width:400px;" class="text_medium" type="text" id="linkhint">');

		$tpl->set('s', 'ZUORDNUNGSID', "idcatart");
		$tpl->set('s', 'ALLOCID', $tmp_cat_art);

		/* Author */
		$tpl->set('s', 'AUTHOR_CREATOR', i18n("Author (Creator)"));
		$tpl->set('s', 'AUTOR-ERSTELLUNGS-NAME', $classuser->getRealnameByUserName($tmp_author).'<input type="hidden" class="bb" name="author" value="'.$auth->auth["uname"].'">'.'&nbsp;');
		$tpl->set('s', 'AUTOR-AENDERUNG-NAME', $classuser->getRealnameByUserName($tmp_modifiedby).'&nbsp;');

		/* Created */
		$tmp_erstellt = ($tmp_firstedit == 1) ? '<input type="hidden" name="created" value="'.date("Y-m-d H:i:s").'">' : '<input type="hidden" name="created" value="'.$tmp_created.'">';
		$tpl->set('s', 'ERSTELLT', i18n("Created"));
		$tpl->set('s', 'ERSTELLUNGS-DATUM', $tmp2_created.$tmp_erstellt);

		/* Last modified */
        $tpl->set('s', 'AUTHOR_MODIFIER', i18n("Author (Modifier)"));
		$tpl->set('s', 'LETZTE-AENDERUNG', i18n("Last modified"));
		$tpl->set('s', 'AENDERUNGS-DATUM', $tmp2_lastmodified.'<input type="hidden" name="lastmodified" value="'.date("Y-m-d H:i:s").'">');

		/* Published */
		$tpl->set('s', 'PUBLISHING_DATE_LABEL', i18n("Publishing date"));
		if($tmp_online){
			$tpl->set('s', 'PUBLISHING_DATE', $tmp2_published);
		}else{
			$tpl->set('s', 'PUBLISHING_DATE', i18n("not yet published"));
		}

		$tpl->set('s', 'PUBLISHER', i18n("Publisher"));
		if($classuser->getRealnameByUserName($tmp_publishedby)!=''){
			$tpl->set('s', 'PUBLISHER_NAME', '<input type="hidden" class="bb" name="publishedby" value="'.$auth->auth["uname"].'">'.$classuser->getRealnameByUserName($tmp_publishedby));
		}else{
			$tpl->set('s', 'PUBLISHER_NAME', '<input type="hidden" class="bb" name="publishedby" value="'.$auth->auth["uname"].'">'.'&nbsp;');
		}

		/* Redirect */
		$tpl->set('s', 'WEITERLEITUNG', i18n("Redirect"));
		$tpl->set('s', 'CHECKBOX', '<input '.$disabled.' onclick="document.getElementById(\'redirect_url\').disabled = !this.checked;" type="checkbox" name="redirect" value="1" '.$tmp_redirect_checked.'>');

		/* Redirect - URL */
		if ($tmp_redirect_checked != '')
		{
			$forceDisable = "";
		} else {
			$forceDisable = "disabled";
		}

		$tpl->set('s', 'URL', '<input type="text" '.$disabled.' '.$forceDisable.' class="text_medium" name="redirect_url" style="width:380px;" id="redirect_url" value="'.htmlspecialchars($tmp_redirect_url).'">');

		/* Redirect - New window */
		if (getEffectiveSetting("articles", "show-new-window-checkbox", "false") == "true")
		{
			$tpl->set('s', 'CHECKBOX-NEWWINDOW', '<input type="checkbox" '.$disabled.' id="external_redirect" name="external_redirect" value="1" '.$tmp_external_redirect_checked.'></td><td><label for="external_redirect">'.i18n("New Window").'</label>');
		} else {
			$tpl->set('s', 'CHECKBOX-NEWWINDOW', '&nbsp;');
		}

		/* Online */
		if ($perm->have_perm_area_action("con", "con_makeonline") ||
		$perm->have_perm_area_action_item("con","con_makeonline", $idcat))
		{
			$tmp_ocheck = ($tmp_online != 1) ? '<input '.$disabled.' id="online" type="checkbox" name="online" value="1">' : '<input type="checkbox" '.$disabled.' id="online" name="online" value="1" checked="checked">';
		} else {
			$tmp_ocheck = ($tmp_online != 1) ? '<input disabled="disabled" type="checkbox" name="" value="1">' : '<input disabled="disabled" type="checkbox" name="" value="1" checked="checked">';
		}

		$tpl->set('s', 'ONLINE', 'Online');
		$tpl->set('s', 'ONLINE-CHECKBOX', $tmp_ocheck);


		/* Startartikel */
		if ($perm->have_perm_area_action("con", "con_makestart") ||
		$perm->have_perm_area_action_item("con","con_makestart", $idcat))
		{
			$tmp_start = ($tmp_is_start == 0) ? '<input '.$disabled.' id="is_start" type="checkbox" name="is_start" value="1">' : '<input '.$disabled.' type="checkbox" name="is_start" id="is_start" value="1" checked="checked">';
		} else {
			$tmp_start = ($tmp_is_start == 0) ? '<input disabled="disabled" type="checkbox" name="" value="1">' : '<input disabled="disabled" type="checkbox" name="" value="1" checked="checked">';
		}
		$tpl->set('s', 'STARTARTIKEL', i18n("Start article"));
		$tpl->set('s', 'STARTARTIKEL-CHECKBOX', $tmp_start);

		/* Sortierung */
		$tpl->set('s', 'SORTIERUNG', i18n("Sort key"));
		$tpl->set('s', 'SORTIERUNG-FIELD', '<input type="text" '.$disabled.' class="text_medium" name="artsort" style="width:400px;" value="'.$tmp_sort.'">');

		/* Category select */

		/* Fetch setting */
		$oClient = new cApiClient($client);
		$cValue = $oClient->getProperty("system", "multiassign", true);
		$sValue = getSystemProperty("system", "multiassign", true);

		$tpl2 = new Template;
		$button = "";
		$moveOK = true;

		if ($cValue == false || $sValue == false)
		{
			$sql = "SELECT idartlang, online FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND online='1' AND idlang != '".Contenido_Security::toInteger($lang)."'";
			$db->query($sql);
				
			if ($db->num_rows() > 0)
			{
				$moveOK = false;
			} else {
				$moveOK = true;
			}
				
			if ($moveOK == true)
			{
				if (count(conGetCategoryAssignments($idart)) > 1)
				{
					/* Old behaviour */
					$tpl2 = new Template;
					$tpl2->set('s', 'ID',       'catsel');
					$tpl2->set('s', 'NAME',     'fake[]');
					$tpl2->set('s', 'CLASS',    'text_medium');
					$tpl2->set('s', 'OPTIONS',  'multiple="multiple" disabled="disabled" size="14" style="width: 400px;scrollbar-face-color:#C6C6D5;scrollbar-highlight-color:#FFFFFF;scrollbar-3dlight-color:#747488;scrollbar-darkshadow-color:#000000;scrollbar-shadow-color:#334F77;scrollbar-arrow-color:#334F77;scrollbar-track-color:#C7C7D6;background:lightgrey;"');
			   
					$rbutton = new cHTMLButton("removeassignment", i18n("Remove assignments"));
			   
					$boxTitle = i18n("Remove multiple category assignments");
					$boxDescr = i18n("Do you really want to remove the assignments to all categories except the current one?");
			   
					$rbutton->setEvent("click", 'box.confirm(\''.$boxTitle.'\', \''.$boxDescr.'\', \'removeAssignments('.$idart.', '.$idcat.')\'); return false;');
					$button = "<br>".$rbutton->render();
			   
					$moveOK = false;
			   
				} else {
					$tpl2 = new Template;
					$tpl2->set('s', 'ID',       'catsel');
					$tpl2->set('s', 'NAME',     'idcatnew[]');
					$tpl2->set('s', 'CLASS',    'text_medium');
					$tpl2->set('s', 'OPTIONS',  'size="14" style="width: 400px;scrollbar-face-color:#C6C6D5;scrollbar-highlight-color:#FFFFFF;scrollbar-3dlight-color:#747488;scrollbar-darkshadow-color:#000000;scrollbar-shadow-color:#334F77;scrollbar-arrow-color:#334F77;scrollbar-track-color:#C7C7D6;"');
				}
			} else {

				$note = i18n("Language parts of the articles are existing in other languages and are online. To change the category assignment, please set the other articles offline first.");
				$tpl2->set('s', 'ID',       'catsel');
				$tpl2->set('s', 'NAME',     'fake[]');
				$tpl2->set('s', 'CLASS',    'text_medium');
				$tpl2->set('s', 'OPTIONS',  'multiple="multiple" disabled="disabled" size="14" style="width: 400px;scrollbar-face-color:#C6C6D5;scrollbar-highlight-color:#FFFFFF;scrollbar-3dlight-color:#747488;scrollbar-darkshadow-color:#000000;scrollbar-shadow-color:#334F77;scrollbar-arrow-color:#334F77;scrollbar-track-color:#C7C7D6;background:lightgrey;"');
			}
				
				
		} else {
			/* Old behaviour */
			$tpl2->set('s', 'ID',       'catsel');
			$tpl2->set('s', 'NAME',     'idcatnew[]');
			$tpl2->set('s', 'CLASS',    'text_medium');
			$tpl2->set('s', 'OPTIONS',  'multiple="multiple" '.$disabled.' size="14" style="width: 400px;scrollbar-face-color:#C6C6D5;scrollbar-highlight-color:#FFFFFF;scrollbar-3dlight-color:#747488;scrollbar-darkshadow-color:#000000;scrollbar-shadow-color:#334F77;scrollbar-arrow-color:#334F77;scrollbar-track-color:#C7C7D6;"');
		}


		if ( isset($tplinputchanged) && $tplinputchanged == 1 ) {
			$tmp_idcat_in_art = $idcatnew;

		} else {
			$sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".$idart."'"; // get all idcats that contain art
			$db->query($sql);

			while ( $db->next_record() ) {
				$tmp_idcat_in_art[] = $db->f("idcat");
			}

			if (!is_array($tmp_idcat_in_art)) {
				$tmp_idcat_in_art[0] = $idcat;
			}
		}

		/* Start date */
		if ($tmp_datestart == "0000-00-00 00:00:00")
		{
			$tpl->set('s', 'STARTDATE', '');
		} else {
			$tpl->set('s', 'STARTDATE', $tmp_datestart);
		}
		 
		 
		/* End date */
		if ($tmp_dateend == "0000-00-00 00:00:00")
		{
			$tpl->set('s', 'ENDDATE','');
		} else {
			$tpl->set('s', 'ENDDATE', $tmp_dateend);
		}

		$sql = "SELECT
                    A.idcat,
                    A.level,
                    C.name
                FROM
                    ".$cfg["tab"]["cat_tree"]." AS A,
                    ".$cfg["tab"]["cat"]." AS B,
                    ".$cfg["tab"]["cat_lang"]." AS C
                WHERE
                    A.idcat=B.idcat AND
                    B.idcat=C.idcat AND
                    C.idlang='".Contenido_Security::toInteger($lang)."' AND
                    B.idclient='".Contenido_Security::toInteger($client)."'
                ORDER BY
                    A.idtree";

		$db->query($sql);

		while ( $db->next_record() ) {

			$spaces = "";

			for ($i = 0; $i < $db->f("level"); $i ++) {
				$spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;";
			}

			if ( !in_array($db->f("idcat"), $tmp_idcat_in_art) ) {
				$tpl2->set('d', 'VALUE', $db->f("idcat"));
				$tpl2->set('d', 'SELECTED', '');
				$tpl2->set('d', 'CAPTION', $spaces.Contenido_Security::unFilter($db->f("name")));

				$tpl2->next();

			} else {
				$tpl2->set('d', 'VALUE', $db->f("idcat"));
				$tpl2->set('d', 'SELECTED', 'selected="selected"');
				$tpl2->set('d', 'CAPTION', $spaces.Contenido_Security::unFilter($db->f("name")));
				$tpl2->next();

				if ($moveOK == false)
				{
					$button .= '<input type="hidden" name="idcatnew[]" value="'.$db->f("idcat").'">';
				}

			}
		}

		$select = $tpl2->generate($cfg["path"]["templates"] . $cfg["templates"]["generic_select"], true);

		/* Struktur */
		$tpl->set('s', 'STRUKTUR', i18n("Category"));
		$tpl->set('s', 'STRUKTUR-FIELD', $select . $button);

		if (isset($tmp_notification)) {
			$tpl->set('s', 'NOTIFICATION', '<tr><td colspan="4">'.$tmp_notification.'<br></td></tr>');
		} else {
			$tpl->set('s', 'NOTIFICATION', '');
		}

		if (($perm->have_perm_area_action("con", "con_makeonline") ||
		$perm->have_perm_area_action_item("con","con_makeonline", $idcat)) && $inUse == false)
		{
			$allow_usetimemgmt = '';
			$sCalStartInit = '<script type="text/javascript">
  								Calendar.setup(
    							{
			      					inputField  : "datestart",
			      					ifFormat    : "%Y-%m-%d %H:%M",
			      					button      : "trigger_start",
			      					weekNumbers	: true,
			      					firstDay	:	1,
			      					showsTime	: true
		    					}
		  					);
							</script>';
				
			$sCalEndInit = '<script type="text/javascript">
  								Calendar.setup(
    							{
			      					inputField  : "dateend",
			      					ifFormat    : "%Y-%m-%d %H:%M",
			      					button      : "trigger_end",
			      					weekNumbers	: true,
			      					firstDay	:	1,
			      					showsTime	: true
		    					}
		  					);
							</script>';

			$tpl->set('s', 'CHOOSEEND', '<img src="images/calendar.gif" width="16" height="16" style="vertical-align:top;margin-top:2px;" id="trigger_end" alt="'.i18n("Endzeitpunkt wählen").'">'.$sCalEndInit);
			$tpl->set('s', 'CHOOSESTART', '<img src="images/calendar.gif" width="16" height="16" style="vertical-align:top;margin-top:2px;" id="trigger_start" alt="'.i18n("Startzeitpunkt wählen").'">'.$sCalStartInit);
		} else {
			$allow_usetimemgmt = ' disabled="disabled"';
			$tpl->set('s', 'CHOOSEEND', '');
			$tpl->set('s', 'CHOOSESTART', '');
		}

		$tpl->set('s', 'SDOPTS', $allow_usetimemgmt);
		$tpl->set('s', 'EDOPTS', $allow_usetimemgmt);

		if ($tmp_usetimemgmt == '1')
		{
			$tpl->set('s','TIMEMGMTCHECKED', 'checked'.$allow_usetimemgmt);
		} else {
			$tpl->set('s', 'TIMEMGMTCHECKED', $allow_usetimemgmt);
		}

		unset ($tpl2);
		/* Nach Kategorie Verschieben */
		$tpl2 = new Template;
		$tpl2->set('s', 'ID',       'catsel');
		$tpl2->set('s', 'NAME',     'time_target_cat');
		$tpl2->set('s', 'CLASS',    'text_medium');
		$tpl2->set('s', 'OPTIONS',  'size="1" style="width: 160px;scrollbar-face-color:#C6C6D5;scrollbar-highlight-color:#FFFFFF;scrollbar-3dlight-color:#B3B3B3;scrollbar-darkshadow-color:#000000;scrollbar-shadow-color:#334F77;scrollbar-arrow-color:#334F77;scrollbar-track-color:#C7C7D6;"'.$allow_usetimemgmt);

		$sql = "SELECT
                    A.idcat,
                    A.level,
                    C.name
                FROM
                    ".$cfg["tab"]["cat_tree"]." AS A,
                    ".$cfg["tab"]["cat"]." AS B,
                    ".$cfg["tab"]["cat_lang"]." AS C
                WHERE
                    A.idcat=B.idcat AND
                    B.idcat=C.idcat AND
                    C.idlang='".Contenido_Security::toInteger($lang)."' AND
                    B.idclient='".Contenido_Security::toInteger($client)."'
                ORDER BY
                    A.idtree";

		$db->query($sql);

		while ( $db->next_record() ) {

			$spaces = "";

			for ($i = 0; $i < $db->f("level"); $i ++) {
				$spaces .= "&nbsp;&nbsp;";
			}

			if ( $db->f("idcat") != $tmp_targetcat) {
				$tpl2->set('d', 'VALUE', $db->f("idcat"));
				$tpl2->set('d', 'SELECTED', '');
				$tpl2->set('d', 'CAPTION', $spaces.Contenido_Security::unFilter($db->f("name")));

				$tpl2->next();

			} else {
				$tpl2->set('d', 'VALUE', $db->f("idcat"));
				$tpl2->set('d', 'SELECTED', 'selected="selected"');
				$tpl2->set('d', 'CAPTION', $spaces.Contenido_Security::unFilter($db->f("name")));
				$tpl2->next();

			}
		}

		$select = $tpl2->generate($cfg["path"]["templates"] . $cfg["templates"]["generic_select"], true);

		/* Seitentitel */
		$title_input = '<input type="text" '.$disabled.' class="text_medium" name="page_title" style="width:400px;" value="'.htmlspecialchars($tmp_page_title).'">';
		$tpl->set("s", "TITLE-INPUT", $title_input);

		/* Meta-Tags */
		$availableTags = conGetAvailableMetaTagTypes();
		
        $sMetaDate =   '<script type="text/javascript">
                            Calendar.setup(
                            {
                                inputField  : "METAdate",
                                ifFormat    : "%Y-%m-%d %H:%M",
                                button      : "METAdate_button",
                                weekNumbers	: true,
                                firstDay	:	1,
                                showsTime	: true
                            }
                        );
                        </script>';        
		 
		foreach ($availableTags as $key => $value)
		{
			$tpl->set('d', 'METAINPUT', 'META'.$value);

			switch ($value["fieldtype"])
			{
				case "text":
                    if ($value["name"] == 'date') {
                        $element = '<input '.$disabled.' class="text_medium" type="text" name="META'.$value["name"].'" id="META'.$value["name"].'" style="width:380px;" maxlength='.$value["maxlength"].' value="'.htmlspecialchars(conGetMetaValue($tmp_idartlang,$key)).'">
                                    <img src="images/calendar.gif" width="16" height="16" style="vertical-align:top;margin-top:2px;" id="METAdate_button" title="'.i18n("Select date").'" alt="'.i18n("Select date").'">'.$sMetaDate;
                    } else {
                        $element = '<input '.$disabled.' class="text_medium" type="text" name="META'.$value["name"].'" id="META'.$value["name"].'" style="width:400px;" maxlength='.$value["maxlength"].' value="'.htmlspecialchars(conGetMetaValue($tmp_idartlang,$key)).'">';
					}
                    break;
				case "textarea":
					$element = '<textarea '.$disabled.' class="text_medium" name="META'.$value["name"].'" id="META'.$value["name"].'" style="width:400px;" rows=3>'.htmlspecialchars(conGetMetaValue($tmp_idartlang,$key)).'</textarea>';
					break;
			}
            
            

			$tpl->set('d', 'METAFIELDTYPE', $element);
			//$tpl->set('d', 'METAVALUE', conGetMetaValue($tmp_idartlang,$key));
			$tpl->set('d', 'METATITLE', $value["name"].':');
			$tpl->next();
		}

		/* Struktur */
		$tpl->set('s', 'MOVETOCATEGORYSELECT', $select);


		if ($tmp_movetocat == "1")
		{
			$tpl->set('s','MOVETOCATCHECKED', 'checked'.$allow_usetimemgmt);
		} else {
			$tpl->set('s','MOVETOCATCHECKED', ''.$allow_usetimemgmt);
		}

		if ($tmp_onlineaftermove == "1")
		{
			$tpl->set('s', 'ONLINEAFTERMOVECHECKED', 'checked'.$allow_usetimemgmt);
		} else {
			$tpl->set('s', 'ONLINEAFTERMOVECHECKED', ''.$allow_usetimemgmt);
		}

		/* Summary */
		$tpl->set('s', 'SUMMARY', i18n("Summary"));
		$tpl->set('s', 'SUMMARY-INPUT', '<textarea '.$disabled.' style="width: 400px" class="text_medium" name="summary" cols="50" rows="5">'.$tmp_summary.'</textarea>');

		$sql = "SELECT
                    b.idcat
                FROM
                    ".$cfg["tab"]["cat"]." AS a,
                    ".$cfg["tab"]["cat_lang"]." AS b,
                    ".$cfg["tab"]["cat_art"]." AS c
                WHERE
                    a.idclient = '".Contenido_Security::toInteger($client)."' AND
                    a.idcat    = b.idcat AND
                    c.idcat    = b.idcat AND
                    c.idart    = '".Contenido_Security::toInteger($idart)."'";

		$db->query($sql);
		$db->next_record();

		$midcat = $db->f("idcat");

		if ( isset($idart) ) {

			if ( !isset($idartlang) || 0 == $idartlang ) {
				$sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
				$db->query($sql);
				$db->next_record();
				$idartlang = $db->f("idartlang");
			}

		}

		if ( isset($midcat) ) {

			if ( !isset($idcatlang) || 0 == $idcatlang ) {
				$sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($midcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
				$db->query($sql);
				$db->next_record();
				$idcatlang = $db->f("idcatlang");
			}

		}

		if ( isset($midcat) && isset($idart) ) {

			if ( !isset($idcatart) || 0 == $idcatart ) {
				$sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idcat = '".Contenido_Security::toInteger($midcat)."'";
				$db->query($sql);
				$db->next_record();
				$idcatart = $db->f("idcatart");
			}

		}

		if ( 0 != $idart && 0 != $midcat ) {
			$script = 'artObj.setProperties("'.$idart.'", "'.$idartlang.'", "'.$midcat.'", "'.$idcatlang.'", "'.$idcatart.'", "'.$lang.'");';
        } else {
			$script = 'artObj.reset();';
		}

		$tpl->set('s', 'DATAPUSH', $script);

		$tpl->set('s', 'BUTTONDISABLE', $disabled);
		 
		if ($inUse == true)
		{
			$tpl->set('s', 'BUTTONIMAGE', 'but_ok_off.gif');
		} else {
			$tpl->set('s', 'BUTTONIMAGE', 'but_ok.gif');
		}

		$tpl->set('s', 'CAL_LANG', substr(strtolower($belang), 0, 2));

		/* Genereate the Template */
		$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_edit_form']);

	} else {

		/* User hat no permission
		 to see this form  */
		$notification->displayNotification("error", i18n("Permission denied"));

	}
}
?>
