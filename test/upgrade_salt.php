<?php

$link = mysql_connect("localhost", "root", "");
mysql_select_db("contenido", $link);

echo("UPDATE con_frontendusers SET password='".md5("test")."' WHERE username='test'<br>");
mysql_query("UPDATE con_frontendusers SET password='".md5("test")."' WHERE username='test'", $link);

echo("ALTER TABLE con_frontendusers CHANGE password password VARCHAR(64)<br>");
mysql_query("ALTER TABLE con_frontendusers CHANGE password password VARCHAR(64)", $link);
echo("ALTER TABLE con_frontendusers ADD salt VARCHAR(32) AFTER password<br>");
mysql_query("ALTER TABLE con_frontendusers ADD salt VARCHAR(32) AFTER password", $link);

echo("SELECT * FROM con_frontendusers<br>");
$result = mysql_query("SELECT * FROM con_frontendusers", $link);
while($row = mysql_fetch_assoc($result)) {
    if($row["salt"] == "") {
        $salt = md5($row["username"].rand(1000, 9999).rand(1000, 9999).rand(1000, 9999));
        echo("UPDATE con_frontendusers SET salt='".$salt."' WHERE idfrontenduser='".$row["idfrontenduser"]."'<br>");
        mysql_query("UPDATE con_frontendusers SET salt='".$salt."' WHERE idfrontenduser='".$row["idfrontenduser"]."'", $link);
        echo("UPDATE con_frontendusers SET password='".hash("sha256", $row["password"].$salt)."' WHERE idfrontenduser='".$row["idfrontenduser"]."'<br>");
        mysql_query("UPDATE con_frontendusers SET password='".hash("sha256", $row["password"].$salt)."' WHERE idfrontenduser='".$row["idfrontenduser"]."'", $link);
    }
}

mysql_close($link);

?>