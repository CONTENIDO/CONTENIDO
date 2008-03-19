<?php
/******************************************
* File      :   include.rights_overview.php
* Project   :   Contenido
* Descr     :   Displays rights 
*
* Author    :   Timo A. Hummel
* Created   :   30.04.2003
* Modified  :   30.04.2003
*
* © four for business AG
*****************************************/

$idclient = 2;
$idlang = 2;
die;

$sql = 'SELECT * FROM '.$cfg["tab"]["rights"].' WHERE idlang = 2 AND idclient = 2 AND user_id = \"'.$userid.'\"';
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
                            user_id = "'.$userid.'"';

                $db->query($sql);
            } else {
                $error = "Passwords don't match";
            }
        }
        
        $sql = 'UPDATE
                    '.$cfg["tab"]["phplib_auth_user_md5"].'
                SET
                    realname="'.$realname.'",
                    email="'.$email.'",
                    telephone="'.$telephone.'",
                    address_street="'.$address_street.'",
                    address_city="'.$address_city.'",
                    address_country="'.$address_country.'",
                    wysi="'.$wysi.'"
                WHERE
                    user_id = "'.$userid.'"';
                    
        $db->query($sql);
    }

    $tpl->reset();
    
    $sql = "SELECT
                username, password, realname, email, telephone,
                address_street, address_city, address_country, wysi
            FROM
                ".$cfg["tab"]["phplib_auth_user_md5"]."
            WHERE
                user_id = '".$userid."'";

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
    $tpl->set('s', 'SUBMITTEXT', "Änderungen &uuml;bernehmen");
    if ($error)
    {
        echo $error;
    }
    $tpl->set('d', 'CATNAME', "Name");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "realname", $db->f("realname"), 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "Neues Passwort");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("password", "password", "", 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "Neues Passwort (Bestätigung)");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("password", "passwordagain", "", 40, 255));
    $tpl->next();

    $tpl->set('d', 'CATNAME', "E-Mail");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "email", $db->f("email"), 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "Telefon");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "telephone", $db->f("telephone"), 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "Strasse");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_street", $db->f("address_street"), 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "Stadt");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_city", $db->f("address_city"), 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "Land");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "address_country", $db->f("address_country"), 40, 255));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', "WYSIWYG-Editor");
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', formGenerateCheckbox("wysi", "1", $db->f("wysi")));
    $tpl->next();
    

    # Generate template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_details']);
}
?>
