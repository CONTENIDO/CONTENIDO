<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Display rights
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2003-04-30
 *   modified 2008-06-24, Timo Trautmann, storage for valid from valid to added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-08-26, Timo Trautmann - fixed CON-200 - User can only get lang rights, if he has client access
 *   modified 2008-10-??, Bilal Arslan - direct DB user modifications are now encapsulated in new ConUser class
 *   modified 2008-11-17, Holger Librenz - method calls for new user object modified, comments updated
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *
 *   $Id$:
 * }}
 *
 * TODO error handling!!!
 * TODO export functions to new ConUser object!
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude('includes', 'functions.rights.php');
$users = new Users;

// New Class User, update password and other values
$oUser = new ConUser($cfg, $db);

if (($action == "user_delete") && ($perm->have_perm_area_action('user', $action))) {

   $users->deleteUserByID($userid);

   $sql = "DELETE FROM "
			.$cfg["tab"]["groupmembers"]."
				WHERE user_id = '".Contenido_Security::escapeDB($userid, $db)."'";
	$db->query($sql);

	$sql = "DELETE FROM ".
   			$cfg["tab"]["rights"].
   			" WHERE user_id = \"".Contenido_Security::escapeDB($userid, $db)."\"";

   $db->query($sql);

  $userid = "";

}

$db2 = new DB_Contenido;

if( ! ($perm->have_perm_area_action($area,$action) || $perm->have_perm_area_action('user',$action)) )
{
  $notification->displayNotification("error", i18n("Permission denied"));
} else {

if ( !isset($userid) )
{

} else {

    $oUser->setUserId ($userid);

    if (($action == "user_edit") && ($perm->have_perm_area_action($area, $action)))
    {
            $stringy_perms = array();
            if ($msysadmin)
            {
                array_push($stringy_perms, "sysadmin");
            }

            if (is_array($madmin)) {
                foreach ($madmin as $value) {
                    array_push($stringy_perms, "admin[$value]");
                }
            }

            if (is_array($mclient)) {
                foreach ($mclient as $value) {
                    array_push($stringy_perms, "client[$value]");
                }
            }

            //Fixed CON-200
            if (!is_array($mclient)) {
                $mclient = array();
            }

            if (is_array($mlang)) {
                foreach ($mlang as $value) {
                    //Fixed CON-200
                    if (checkLangInClients($mclient, $value, $cfg, $db)) {
                        array_push($stringy_perms, "lang[$value]");
                    }
                }
            }

            // update user values
  			$oUser->setRealName($realname);
			$oUser->setMail($email);
			$oUser->setTelNumber($telephone);
			$oUser->setAddressData($address_street, $address_city, $address_zip, $address_country);
			$oUser->setUseTiny($wysi);
			$oUser->setValidDateFrom($valid_from);
			$oUser->setValidDateTo($valid_to);

			$oUser->setPerms($stringy_perms);

			// is a password set?
			$bPassOk = false;
            if (strlen($password) > 0) {
                // yes --> check it...
                if (strcmp($password, $passwordagain) == 0) {
					// set password....
                    $iPasswordSaveResult = $oUser->setPassword($password);

                    // fine, passwords are the same, but is the password valid?
                    if ($iPasswordSaveResult != iConUser::PASS_OK) {
                        // oh oh, password is NOT valid. check it...
                        $sPassError = ConUser::getErrorString($iPasswordSaveResult, $cfg);
                        $notification->displayNotification("error", $sPassError);
                    } else {
                        $bPassOk = true;
                    }
                } else {
                    $notification->displayNotification("error", i18n("Passwords don't match"));
                }
        }

        if (strlen($password) == 0 || $bPassOk == true) {
            try {
                // save, if no error occured..
                if ($oUser->save()) {
                    $notification->displayNotification("info", i18n("Changes saved"));
                } else {
            	   $notification->displayNotification("error", i18n("An error occured while saving user info."));
            	}

            } catch (ConUserException $cue) {
                // TODO make check and info ouput better!
                $notification->displayNotification("error", i18n("An error occured while saving user info."));
            }

        }
    }

    // TODO port this to new ConUser class!
    $tpl->reset();
    $tpl->set('s','SID', $sess->id);
    $sql = "SELECT
                username, password, realname, email, telephone,
                address_street, address_zip, address_city, address_country, wysi, valid_from, valid_to
            FROM
                ".$cfg["tab"]["phplib_auth_user_md5"]."
            WHERE
                user_id = '".Contenido_Security::escapeDB($userid, $db)."'";

    $db->query($sql);

    if(!isset($rights_perms)||$action==""||!isset($action)){

        $db3 = new DB_Contenido;
        //search for the permissions of this user
        $sql="SELECT perms FROM ".$cfg["tab"]["phplib_auth_user_md5"]." WHERE user_id='".Contenido_Security::escapeDB($userid, $db)."'";

        $db3->query($sql);
        $db3->next_record();
        $rights_perms=$db3->f("perms");

    }

    $user_perms = array();
    $user_perms = explode(",", $rights_perms);

    $form = '<form name="user_properties" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="user_edit">
                 <input type="hidden" name="frame" value="'.$frame.'">
                 <input type="hidden" name="userid" value="'.$userid.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';

    $db->next_record();

    $tpl->set('s', 'JAVASCRIPT', $javascript);
    $tpl->set('s', 'FORM', $form);
    $tpl->set('s', 'GET_USERID', $userid);
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
    $tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
    $tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&userid=$userid"));

    if ($error)
    {
        echo $error;
    }

    $tpl->set('d', 'CLASS', 'textg_medium');
    $tpl->set('d', 'CATNAME', i18n("Property"));
    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', i18n("Value"));
    $tpl->set('d', 'BRDT', 1);
    $tpl->set('d', 'BRDB', 0);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Username"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', $db->f("username").'<img align="top" src="images/spacer.gif" height="20">');
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Name"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "realname", $db->f("realname"), 40, 255));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    // @since 2006-07-04 Display password fields only if not authenticated via LDAP/AD
    if ($msysadmin || $db->f("password") != 'active_directory_auth') {
	    $tpl->set('d', 'CLASS', 'text_medium');
	    $tpl->set('d', 'CATNAME', i18n("New password"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("password", "password", "", 40, 255));
		$tpl->set('d', 'BRDT', 0);
		$tpl->set('d', 'BRDB', 1);
	    $tpl->next();

	    $tpl->set('d', 'CLASS', 'text_medium');
	    $tpl->set('d', 'CATNAME', i18n("Confirm new password"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("password", "passwordagain", "", 40, 255));
		$tpl->set('d', 'BRDT', 0);
		$tpl->set('d', 'BRDB', 1);
	    $tpl->next();
    }

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("E-Mail"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "email", $db->f("email"), 40, 255));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Phone number"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "telephone", $db->f("telephone"), 40, 255));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Street"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_street", $db->f("address_street"), 40, 255));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("ZIP code"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_zip", $db->f("address_zip"), 10, 10));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("City"));
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_city", $db->f("address_city"), 40, 255));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Country"));
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_country", $db->f("address_country"), 40, 255));
    $tpl->set('d', 'BRDT', 0);
    $tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $userperm = explode(',', $auth->auth['perm']);

    if(in_array("sysadmin",$userperm)){
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("System administrator"));
        $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
        $tpl->set('d', "CATFIELD", formGenerateCheckbox("msysadmin","1", in_array("sysadmin", $user_perms)));
		$tpl->set('d', 'BRDT', 0);
		$tpl->set('d', 'BRDB', 1);
        $tpl->next();
    }


    $sql="SELECT * FROM ".$cfg["tab"]["clients"];
    $db2->query($sql);
    $client_list = "";
    $gen = 0;
    while($db2->next_record())
    {
        if(in_array("admin[".$db2->f("idclient")."]",$userperm) || in_array("sysadmin",$userperm)){
            $client_list .= formGenerateCheckbox("madmin[".$db2->f("idclient")."]",$db2->f("idclient"),in_array("admin[".$db2->f("idclient")."]",$user_perms), $db2->f("name")." (".$db2->f("idclient").")")."<br>";
            $gen = 1;
        }
    }

    if ($gen == 1 && !in_array("sysadmin",$user_perms))
    {
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("Administrator"));
        $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
        $tpl->set('d', "CATFIELD", $client_list);
        $tpl->set('d', 'BRDT', 0);
        $tpl->set('d', 'BRDB', 1);
        $tpl->next();
    }

    $sql = "SELECT * FROM " .$cfg["tab"]["clients"];
    $db2->query($sql);
    $client_list = "";

    while ($db2->next_record())
    {
        if((in_array("client[".$db2->f("idclient")."]",$userperm) || in_array("sysadmin",$userperm) || in_array("admin[".$db2->f("idclient")."]",$userperm)) && !in_array("admin[".$db2->f("idclient")."]",$user_perms)) {
            $client_list .= formGenerateCheckbox("mclient[".$db2->f("idclient")."]",$db2->f("idclient"),in_array("client[".$db2->f("idclient")."]",$user_perms), $db2->f("name")." (". $db2->f("idclient") . ")")."<br>";
        }
    }

    if ($client_list != "" && !in_array("sysadmin",$user_perms))
    {
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("Access clients"));
        $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
        $tpl->set('d', "CATFIELD", $client_list);
		$tpl->set('d', 'BRDT', 0);
		$tpl->set('d', 'BRDB', 1);
        $tpl->next();
    }

    $sql = "SELECT
                a.idlang as idlang,
                a.name as name,
                b.name as clientname,
                b.idclient as idclient FROM
                " .$cfg["tab"]["lang"]." as a,
                " .$cfg["tab"]["clients_lang"]." as c,
                " .$cfg["tab"]["clients"]." as b
                WHERE
                    a.idlang = c.idlang AND
                    c.idclient = b.idclient";

    $db2->query($sql);
    $client_list = "";

    while ($db2->next_record())
    {
        if(($perm->have_perm_client("lang[".$db2->f("idlang")."]") || $perm->have_perm_client("admin[".$db2->f("idclient")."]" )) && !in_array("admin[".$db2->f("idclient")."]",$user_perms))
        {
            $client_list .= formGenerateCheckbox("mlang[".$db2->f("idlang")."]",$db2->f("idlang"),in_array("lang[".$db2->f("idlang")."]",$user_perms), $db2->f("name")." (". $db2->f("clientname") .")") ."<br>";
        }
    }

    if ($client_list != "" && !in_array("sysadmin",$user_perms))
    {
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("Access languages"));
        $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
        $tpl->set('d', "CATFIELD", $client_list);
        $tpl->set('d', 'BRDT', 0);
        $tpl->set('d', 'BRDB', 1);
        $tpl->next();
    }

	/* Generate user property table */
    $tempUser = new User();

    $tempUser->loadUserByUserID($userid);

    if (is_string($del_userprop_type) && is_string($del_userprop_name))
    {
    	$tempUser->deleteUserProperty($del_userprop_type, $del_userprop_name);
    }

    if (is_string($userprop_type) && is_string($userprop_name) && is_string($userprop_value)
        && !empty($userprop_type) && !empty($userprop_name))
    {
    	$tempUser->setUserProperty($userprop_type, $userprop_name, $userprop_value);
    }
    $properties = $tempUser->getUserProperties();

    if (is_array($properties))
    {
    	foreach ($properties as $entry)
    	{
    		$type = $entry["type"];

    		if ($type != "system")
    		{
        		$name = $entry["name"];
        		$deleteButton = '<a href="'.$sess->url("main.php?area=$area&frame=4&userid=$userid&del_userprop_type=$type&del_userprop_name=$name").'"><img src="images/delete.gif" border="0" alt="Eigenschaft löschen" title="Eigenschaft löschen"></a>';
        		$value = $tempUser->getUserProperty($type,$name);
        		$propLines .= "<tr class=\"text_medium\"><td>$type</td><td>$name</td><td>$value</td><td>$deleteButton</tr>";
    		}
    	}
    }
	$table = '<table width="100%" cellspacing="0" cellpadding="2" style="border: 1px; border-color:'.$cfg["color"]["table_border"].'; border-style: solid;">
                 <tr style="background-color:'.$cfg["color"]["table_header"].'" class="text_medium"><td>'.i18n("Area/Type").'</td><td>'.i18n("Property").'</td><td>'.i18n("Value").'</td><td>&nbsp;</td></tr>'. $propLines.
			 '<tr class="text_medium"><td><input class="text_medium"  type="text" size="16" maxlen="32" name="userprop_type"></td>
              <td><input class="text_medium" type="text" size="16" maxlen="32" name="userprop_name"></td>
			  <td><input class="text_medium" type="text" size="32" name="userprop_value"></td><td>&nbsp;</td></tr></table>';

	$userProps = $table;

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("User-defined properties"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
    $tpl->set('d', "CATFIELD", $userProps);
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Use WYSIWYG-Editor"));
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateCheckbox("wysi", "1", $db->f("wysi")));
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();

	$sCurrentValueFrom = str_replace('00:00:00', '', $db->f("valid_from"));
	$sCurrentValueFrom = trim(str_replace('0000-00-00', '', $sCurrentValueFrom));

	$sInputValidFrom = '<style type="text/css">@import url(./scripts/jscalendar/calendar-contenido.css);</style>
					<script type="text/javascript" src="./scripts/jscalendar/calendar.js"></script>
					<script type="text/javascript" src="./scripts/jscalendar/lang/calendar-'.substr(strtolower($belang),0,2).'.js"></script>
					<script type="text/javascript" src="./scripts/jscalendar/calendar-setup.js"></script>';
	$sInputValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="'.$sCurrentValueFrom.'" />&nbsp;<img src="images/calendar.gif" id="trigger" /">';
	$sInputValidFrom .= '<script type="text/javascript">
  					Calendar.setup(
    					{
      					inputField  : "valid_from",
      					ifFormat    : "%Y-%m-%d",
      					button      : "trigger",
      					weekNumbers	: true,
      					firstDay	:	1
    					}
  					);
					</script>';

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Valid from"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
    $tpl->set('d', "CATFIELD", $sInputValidFrom);
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();

	$sCurrentValueTo = str_replace('00:00:00', '', $db->f("valid_to"));
	$sCurrentValueTo = trim(str_replace('0000-00-00', '', $sCurrentValueTo));

	$sInputValidTo = '<input type="text" id="valid_to" name="valid_to" value="'.$sCurrentValueTo.'" />&nbsp;<img src="images/calendar.gif" id="trigger_to" /">';
	$sInputValidTo .= '<script type="text/javascript">
  							Calendar.setup(
    							{
								inputField  : "valid_to",
								ifFormat    : "%Y-%m-%d",
								button      : "trigger_to",
		      					weekNumbers	: true,
		      					firstDay	:	1
							    }
							);
							</script>';

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Valid to"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
    $tpl->set('d', "CATFIELD", $sInputValidTo);
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();

	if ($sCurrentValueFrom == '') {
		$sCurrentValueFrom = '0000-00-00';
	}

	if (($sCurrentValueTo == '') || ($sCurrentValueTo == '0000-00-00')) {
		$sCurrentValueTo = '9999-99-99';
	}

	$sCurrentDate = date('Y-m-d');
	$bAccountActive = true;

	if (($sCurrentValueFrom > $sCurrentDate) || ($sCurrentValueTo < $sCurrentDate)) {
		$bAccountActive = false;
	}

	if ($bAccountActive) {
		$sAccountState = i18n("This account is currently active.");
		$sAccountColor = "green";
	} else {
		$sAccountState = i18n("This account is currently inactive.");
		$sAccountColor = "red";
	}

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', '&nbsp;');
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
    $tpl->set('d', "CATFIELD", '<span style="color:'.$sAccountColor.';">'.$sAccountState.'</span>');
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();

	#Show backend user's group memberships
	$arrGroups = $tempUser->getGroupsByUserID ($userid);

	if (count($arrGroups) > 0) {
		asort($arrGroups);
		$sGroups = implode("<br/>", $arrGroups);
	} else {
		$sGroups = i18n("none");
	}

    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Group membership"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
    $tpl->set('d', "CATFIELD", $sGroups);
	$tpl->set('d', 'BRDT', 0);
	$tpl->set('d', 'BRDB', 1);
    $tpl->next();


    # Generate template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_overview']);
}
}
?>
