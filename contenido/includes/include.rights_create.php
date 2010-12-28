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
 * @version    1.0.2
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
 *   modified 2008-11-17, H. Librenz - new ConUser class are used for user creation now, comments fixed, code formatted
 *   modified 2008-11-18, H. Librenz - values given during a submittion try are now resubmitted
 *   modified 2010-05-31, Ortwin Pinke, PHP >= 5.3, replace deprecated split-function with explode()
 *
 *   $Id$:
 * }}
 *
 */

if (! defined ( 'CON_FRAMEWORK' )) {
	die ( 'Illegal call' );
}

cInclude ( 'includes', 'functions.rights.php' );

if (! $perm->have_perm_area_action ( $area, $action )) {
	$notification->displayNotification ( "error", i18n ( "Permission denied" ) );
} else {

	if ($action == "user_createuser") {
		if ($username == "") {
			$error = i18n ( "Username can't be empty" );
		} else {

			$stringy_perms = array ();
			if ($msysadmin) {
				array_push ( $stringy_perms, "sysadmin" );
			}

			if (is_array ( $madmin )) {
				foreach ( $madmin as $value ) {
					array_push ( $stringy_perms, "admin[$value]" );
				}
			}

			if (is_array ( $mclient )) {
				foreach ( $mclient as $value ) {
					array_push ( $stringy_perms, "client[$value]" );
				}
			} else {
				// Add user to the current client, if the current user
				// isn't sysadmin and no client has been specified.
				// This avoids new accounts which are not accessible by the
				// current user (client admin) anymore
				$aUserPerm = explode( ",", $auth->auth ["perm"] );

				if (! in_array ( "sysadmin", $aUserPerm )) {
					array_push ( $stringy_perms, "client[$client]" );
				}
			}

			//Fixed CON-200
			if (! is_array ( $mclient )) {
				$mclient = array ();
			}

			if (is_array ( $mlang )) {
				foreach ( $mlang as $value ) {
					//Fixed CON-200
					if (checkLangInClients ( $mclient, $value, $cfg, $db )) {
						array_push ( $stringy_perms, "lang[$value]" );
					}
				}
			}

//			$sql = "SELECT user_id FROM " . $cfg ["tab"] ["phplib_auth_user_md5"] . ' WHERE LOWER(username) = "' . Contenido_Security::escapeDB ( strtolower ( $username ), $db ) . '"';
//			$db->query ( $sql );
//
//			if ($db->next_record ()) {
//				$error = i18n ( "Username already exists" );
//			} else {

                $oUser = new ConUser($cfg, $db);

				if (strcmp ( $password, $passwordagain ) == 0) {
//					$newuserid = md5 ( $username );
//					$sql = 'INSERT INTO
//					                            ' . $cfg ["tab"] ["phplib_auth_user_md5"] . '
//					                          SET
//					                		    username="' . Contenido_Security::escapeDB ( $username, $db ) . '",
//					                            password="' . md5 ( $password ) . '",
//					                            realname="' . Contenido_Security::escapeDB ( $realname, $db ) . '",
//					                            email="' . Contenido_Security::escapeDB ( $email, $db ) . '",
//					                            telephone="' . Contenido_Security::escapeDB ( $telephone, $db ) . '",
//					                            address_street="' . Contenido_Security::escapeDB ( $address_street, $db ) . '",
//					                            address_city="' . Contenido_Security::escapeDB ( $address_city, $db ) . '",
//					                            address_country="' . Contenido_Security::escapeDB ( $address_country, $db ) . '",
//					        	        	    address_zip="' . Contenido_Security::escapeDB ( $address_zip, $db ) . '",
//					                            wysi="' . Contenido_Security::toInteger ( $wysi ) . '",
//                                                valid_from="' . Contenido_Security::escapeDB ( $valid_from, $db ) . '",
//							                    valid_to="' . Contenido_Security::escapeDB ( $valid_to, $db ) . '",
//					                            perms="' . implode ( ",", $stringy_perms ) . '",
//					        		            user_id="' . Contenido_Security::escapeDB ( $newuserid, $db ) . '"';

//					$db->query ( $sql );

                    // ok, both passwords given are equal, but is the password valid?
                    $iPassCheck = $oUser->setPassword($password);

                    if ($iPassCheck == iConUser::PASS_OK ) {
                        // yes, it is....
    					try {

    						$oUser->setUserName($username);
    						$oUser->setRealName($realname);
    						$oUser->setMail($email);
    						$oUser->setTelNumber($telephone);
    						$oUser->setStreet($address_street);
    						$oUser->setCity($address_city);
    						$oUser->setZip($address_zip);
    						$oUser->setCountry($address_country);
    						$oUser->setUseTiny($wysi);
    						$oUser->setValidDateFrom($valid_from);
    						$oUser->setValidDateTo($valid_to);
    						$oUser->setPerms($stringy_perms);
    						$oUser->setPassword($password);

    						if ($oUser->save()) {

        						// save user id and clean "old" values...
        						$userid = $oUser->getUserId();

        						$username = "";
        						$realname = "";
        						$email = "";
        						$telephone = "";
        						$address_city = "";
        						$address_country = "";
        						$address_street = "";
        						$address_zip = "";
        						$wysi = "";
        						$valid_from = "";
        						$valid_to = "";
        						$stringy_perms = array();
        						$password = "";
    						}

    					} catch (ConUserException $cue) {

    					    switch ($cue->getCode()) {
    					        case iConUser::EXCEPTION_USERNAME_EXISTS: {
    					            $error = i18n ( "Username already exists" );
    					            break;
    					        }

    					        default: {
    					            $error = i18n( "Unknown error") . ": " . $cue->getMessage();
    					            break;
    					        }
    					    }
    					}
                    } else {
                        // oh oh, password is NOT valid. check it...
                        $error = ConUser::getErrorString($iPassCheck, $cfg);
                    }


				} else {
					$error = i18n ( "Passwords don't match" );
				}
//			}
		}

	}

	$tpl->reset ();

//	$sql = "SELECT
//	                username, password, realname, email, telephone,
//	                address_street, address_zip, address_city, address_country, wysi
//	            FROM
//	                " . $cfg ["tab"] ["phplib_auth_user_md5"] . "
//	            WHERE
//	                user_id = '" . Contenido_Security::escapeDB ( $userid, $db ) . "'";
//
//	$db->query ( $sql );

	$user_perms = array ();
	$user_perms = explode ( ",", $rights_perms );
	$db2 = new DB_Contenido ( );

	$form = '<form name="user_properties" method="post" action="' . $sess->url ( "main.php?" ) . '">
	                 ' . $sess->hidden_session () . '
	                 <input type="hidden" name="area" value="' . $area . '">
	                 <input type="hidden" name="action" value="user_createuser">
	                 <input type="hidden" name="frame" value="' . $frame . '">
	                 <input type="hidden" name="idlang" value="' . $lang . '">';

//	$db->next_record ();

	$tpl->set ( 's', 'FORM', $form );
	$tpl->set ( 's', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 's', 'BGCOLOR', $cfg ["color"] ["table_dark"] );
	$tpl->set ( 's', 'SUBMITTEXT', i18n ( "Save changes" ) );
	if ($error) {
		$notification->displayNotification ( "warning", $error );
	}

	$tpl->set ( 'd', 'CATNAME', i18n ( "Property" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_header"] );
	$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', i18n ( "Value" ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Username" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "username", $username, 40, 32 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Name" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "realname", $realname, 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "New password" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "password", "password", "", 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Confirm new password" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "password", "passwordagain", "", 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "E-Mail" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "email", $email, 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Phone number" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "telephone", $telephone, 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Street" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "address_street", $address_street, 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "ZIP code" ) );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "address_zip", $address_zip, 10, 10 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "City" ) );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "address_city", $address_city, 40, 255 ) );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Country" ) );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateField ( "text", "address_country", $address_country, 40, 255 ) );
	$tpl->next ();

	$userperm = explode( ",", $auth->auth ["perm"] );

	if (in_array ( "sysadmin", $userperm )) {
		$tpl->set ( 'd', 'CLASS', 'text_medium' );
		$tpl->set ( 'd', 'CATNAME', i18n ( "System administrator" ) );
		$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
		$tpl->set ( 'd', "BGCOLOR", $cfg ["color"] ["table_light"] );
		$tpl->set ( 'd', "CATFIELD", formGenerateCheckbox ( "msysadmin", "1", in_array ( "sysadmin", $user_perms ) ) );
		$tpl->next ();
	}

	$sql = "SELECT * FROM " . $cfg ["tab"] ["clients"];
	$db2->query ( $sql );
	$client_list = "";
	$gen = 0;
	while ( $db2->next_record () ) {

		if (in_array ( "admin[" . $db2->f ( "idclient" ) . "]", $userperm ) || in_array ( "sysadmin", $userperm )) {
			$client_list .= formGenerateCheckbox ( "madmin[" . $db2->f ( "idclient" ) . "]", $db2->f ( "idclient" ), in_array ( "admin[" . $db2->f ( "idclient" ) . "]", $user_perms ), $db2->f ( "name" ) . " (" . $db2->f ( "idclient" ) . ")" ) . "<br>";
			$gen = 1;
		}
	}

	if ($gen == 1) {
		$tpl->set ( 'd', 'CLASS', 'text_medium' );
		$tpl->set ( 'd', 'CATNAME', i18n ( "Administrator" ) );
		$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
		$tpl->set ( 'd', "BGCOLOR", $cfg ["color"] ["table_dark"] );
		$tpl->set ( 'd', "CATFIELD", $client_list );
		$tpl->next ();
	}

	$sql = "SELECT * FROM " . $cfg ["tab"] ["clients"];
	$db2->query ( $sql );
	$client_list = "";

	while ( $db2->next_record () ) {
		if (in_array ( "client[" . $db2->f ( "idclient" ) . "]", $userperm ) || in_array ( "sysadmin", $userperm ) || in_array ( "admin[" . $db2->f ( "idclient" ) . "]", $userperm )) {
			$client_list .= formGenerateCheckbox ( "mclient[" . $db2->f ( "idclient" ) . "]", $db2->f ( "idclient" ), in_array ( "client[" . $db2->f ( "idclient" ) . "]", $user_perms ), $db2->f ( "name" ) . " (" . $db2->f ( "idclient" ) . ")" ) . "<br>";
		}

	}
	$tpl->set ( 'd', 'CLASS', 'text_medium' );
	$tpl->set ( 'd', 'CATNAME', i18n ( "Access clients" ) );
	$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', "BGCOLOR", $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', "CATFIELD", $client_list );
	$tpl->next ();

	$sql = "SELECT
	                a.idlang as idlang,
	                a.name as name,
	                b.name as clientname FROM
	                " . $cfg ["tab"] ["lang"] . " as a,
	                " . $cfg ["tab"] ["clients_lang"] . " as c,
	                " . $cfg ["tab"] ["clients"] . " as b
	                WHERE
	                    a.idlang = c.idlang AND
	                    c.idclient = b.idclient";

	$db2->query ( $sql );
	$client_list = "";

	while ( $db2->next_record () ) {
		if ($perm->have_perm_client ( "lang[" . $db2->f ( "idlang" ) . "]" ) || $perm->have_perm_client ( "admin[" . $db2->f ( "idclient" ) . "]" )) {
			$client_list .= formGenerateCheckbox ( "mlang[" . $db2->f ( "idlang" ) . "]", $db2->f ( "idlang" ), in_array ( "lang[" . $db2->f ( "idlang" ) . "]", $user_perms ), $db2->f ( "name" ) . " (" . $db2->f ( "clientname" ) . ")" ) . "<br>";
		}

	}
	$tpl->set ( 'd', 'CLASS', 'text_medium' );
	$tpl->set ( 'd', 'CATNAME', i18n ( "Access languages" ) );
	$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', "BGCOLOR", $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', "CATFIELD", $client_list );
	$tpl->next ();

	$tpl->set ( 'd', 'CATNAME', i18n ( "Use WYSIWYG-Editor" ) );
	$tpl->set ( 'd', "BORDERCOLOR", $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', 'BGCOLOR', $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', 'CATFIELD', formGenerateCheckbox ( "wysi", "1", ((int) $wysi == 1)) );
	$tpl->next ();

	$sInputValidFrom = '<style type="text/css">@import url(./scripts/jscalendar/calendar-contenido.css);</style>
					<script type="text/javascript" src="./scripts/jscalendar/calendar.js"></script>
					<script type="text/javascript" src="./scripts/jscalendar/lang/calendar-' . substr ( strtolower ( $belang ), 0, 2 ) . '.js"></script>
					<script type="text/javascript" src="./scripts/jscalendar/calendar-setup.js"></script>';
	$sInputValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="' . $valid_from . '" />&nbsp;<img src="images/calendar.gif" id="trigger" /">';
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

	$tpl->set ( 'd', 'CLASS', 'text_medium' );
	$tpl->set ( 'd', 'CATNAME', i18n ( "Valid from" ) );
	$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', "BGCOLOR", $cfg ["color"] ["table_dark"] );
	$tpl->set ( 'd', "CATFIELD", $sInputValidFrom );
	$tpl->next ();

	$sInputValidTo = '<input type="text" id="valid_to" name="valid_to" value="' . $valid_to . '" />&nbsp;<img src="images/calendar.gif" id="trigger_to" /">';
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

	$tpl->set ( 'd', 'CLASS', 'text_medium' );
	$tpl->set ( 'd', 'CATNAME', i18n ( "Valid to" ) );
	$tpl->set ( 'd', 'BORDERCOLOR', $cfg ["color"] ["table_border"] );
	$tpl->set ( 'd', "BGCOLOR", $cfg ["color"] ["table_light"] );
	$tpl->set ( 'd', "CATFIELD", $sInputValidTo );
	$tpl->next ();

	# Generate template
	$tpl->generate ( $cfg ['path'] ['templates'] . $cfg ['templates'] ['rights_create'] );
}
?>
