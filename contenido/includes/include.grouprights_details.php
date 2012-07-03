<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Display rights
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-04-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$idclient = 2;
$idlang = 2;
die;

$sql = 'SELECT * FROM '.$cfg["tab"]["rights"].' WHERE idlang = 2 AND idclient = 2 AND user_id = \"'.Contenido_Security::escapeDB($userid, $db).'\"';
echo $sql;

$db->query($sql);

while ($db->next_record())
{
    echo $db->f(0)."<br>";
}

if ( !isset($useridas) )
{

} else {
    if ($action == "user_edit")
    {
        if (strlen($password) > 0)
        {
            if (strcmp($password, $passwordagain) == 0)
            {
                $sql = 'UPDATE
                            '.$cfg["tab"]["phplib_auth_user_md5"].'
                        SET
                            password="'.md5($password).'"
                        WHERE
                            user_id = "'.Contenido_Security::escapeDB($userid, $db).'"';

                $db->query($sql);
            } else {
                $error = "Passwords don't match";
            }
        }

        $sql = 'UPDATE
                    '.$cfg["tab"]["phplib_auth_user_md5"].'
                SET
                    realname="'.Contenido_Security::escapeDB($realname, $db).'",
                    email="'.Contenido_Security::escapeDB($email, $db).'",
                    telephone="'.Contenido_Security::escapeDB($telephone, $db).'",
                    address_street="'.Contenido_Security::escapeDB($address_street, $db).'",
                    address_city="'.Contenido_Security::escapeDB($address_city, $db).'",
                    address_country="'.Contenido_Security::escapeDB($address_country, $db).'",
                    wysi="'.Contenido_Security::toInteger($wysi).'"
                WHERE
                    user_id = "'.Contenido_Security::escapeDB($userid, $db).'"';

        $db->query($sql);
    }

    $tpl->reset();

    $sql = "SELECT
                username, password, realname, email, telephone,
                address_street, address_city, address_country, wysi
            FROM
                ".$cfg["tab"]["phplib_auth_user_md5"]."
            WHERE
                user_id = '".Contenido_Security::escapeDB($userid, $db)."'";

    $db->query($sql);

    $form = '<form name="user_properties" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="user_edit">
                 <input type="hidden" name="frame" value="'.$frame.'">
                 <input type="hidden" name="userid" value="'.$userid.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';

    $db->next_record();


    $tpl->set('s', 'USERNAME', $db->f("username"));
    $tpl->set('s', 'EDITSTRING', "Benutzer editieren:");
    $tpl->set('s', 'FORM', $form);
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('s', 'SUBMITTEXT', "�nderungen &uuml;bernehmen");
    if ($error)
    {
        echo $error;
    }
    $tpl->set('d', 'CATNAME', "Name");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	$oTxtName = new cHTMLTextbox("realname", $db->f("realname"), 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtName->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "Neues Passwort");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	$oTxtPass = new cHTMLPasswordbox("password", "", 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtPass->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "Neues Passwort (Best�tigung)");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	$oTxtWord = new cHTMLPasswordbox("passwordagain", "", 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtWord->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "E-Mail");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	$oTxtEmail = new cHTMLTextbox("email", $db->f("email"), 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtEmail->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "Telefon");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	$oTxtTel= new cHTMLTextbox("telephone", $db->f("telephone"), 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtTel->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "Strasse");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	$oTxtStreet = new cHTMLTextbox("address_street", $db->f("address_street"), 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtStreet->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "Stadt");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	$oTxtCity = new cHTMLTextbox("address_city", $db->f("address_city"), 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtCity->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "Land");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	$oTxtLand = new cHTMLTextbox("address_country", $db->f("address_country"), 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtLand->render());
    $tpl->next();

    $tpl->set('d', 'CATNAME', "WYSIWYG-Editor");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	$oCheckbox = new cHTMLCheckbox("wysi", "1", "wysi1", $db->f("wysi"));
    $tpl->set('d', 'CATFIELD', $oCheckbox->toHTML(false));
    $tpl->next();

    # Generate template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_details']);
}
?>