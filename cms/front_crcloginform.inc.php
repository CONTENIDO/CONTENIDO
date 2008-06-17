<?php

/******************************************
* File      :   main.loginform.php
* Project   :   Contenido
* Descr     :   Login form
*
*
* Author    :   Jan Lengowski
* Created   :   21.01.2003
* Modified  :   21.01.2003
* Modified  :   29.09.2005, Andreas Lindner
*
* © four for business AG
******************************************/

global $cfg, $idcat, $idart, $idcatart, $lang, $client, $username;

$err_catart	= trim(getEffectiveSetting("login_error_page", "idcatart", ""));
$err_cat	= trim(getEffectiveSetting("login_error_page", "idcat", ""));
$err_art	= trim(getEffectiveSetting("login_error_page", "idart", ""));

$sUrl = $cfgClient[$client]["path"]["htmlpath"]."front_content.php";

if ($err_catart!='') {
	header("Location: ".$sUrl."?idcatart=".$err_catart);
}
if ($err_art!='' && $err_cat!='') {
	header("Location: ".$sUrl."?idcat=".$err_cat."&idart=".$err_art);
}
if ($err_cat!='') {
	header("Location: ".$sUrl."?idcat=".$err_cat);
}
if ($err_art!='') {
	header("Location: ".$sUrl."?idart=".$err_art);
}

if (isset($_GET["return"]) || isset($_POST["return"])){
	$aLocator = Array();

	if ($idcat > 0) {
		$aLocator[] = "idcat=".intval($idcat);
	}
	if ($idart > 0) {
		$aLocator[] = "idart=".intval($idart);
	}

	if (isset($_POST["username"]) || isset($_GET["username"])){
		$aLocator[]= "wrongpass=1";
	}

	header ("Location: " . $sUrl . "?" . implode("&", $aLocator));
}
?>
<!doctype html public "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
    <title>:: :: :: :: Contenido Login</title>
    <link rel="stylesheet" type="text/css" href="../contenido/styles/contenido.css" />

    <script language="javascript">
	if (top != self)
	{
		top.location.href = self.location.href;
	}
	</script>
</head>
<body>

<table width="100%" cellspacing="0" cellpadding="0" border="0">
    <!--
    <tr height="70" style="height: 70px">
        <td style="background-image:url(images/background.jpg); border-bottom: 1px solid #000000">
            <img src="images/conlogo.gif">
        </td>
    </tr>-->
    <tr height="400">
        <td align="center" valign="middle">
            <form name="login" method="post" action="front_content.php">
                <table cellspacing="0" cellpadding="3" border="0" style="background-color: <?php echo $cfg['color']['table_light'] ?>; border: 1px solid <?php echo $cfg['color']['table_border'] ?>">
                    <tr>
                        <td colspan="2" class="textw_medium" style="background-color: <?php echo $cfg["color"]["table_header"] ?>; border-bottom: 1px solid <?php echo $cfg["color"]["table_border"] ?>">Login</td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                    </tr>

                    <?php if ( isset($username) ) { ?>
                    <tr>
                        <td colspan="2" class="text_error">Invalid Username or Password!</td>
                    </tr>
                    <?php } else { ?>
                    <tr>
                        <td colspan="2" class="text_error">&nbsp;</td>
                    </tr>
                    <?php } ?>

                    <tr>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td class="text_medium">Username:</td>
                        <td><input type="text" class="text_medium" name="username" size="20" maxlength="32" value="<?php echo ( isset($this->auth["uname"]) ) ? $this->auth["uname"] : ""  ?>"></td>
                    </tr>
                    <tr>
                        <td class="text_medium">Password:</td>
                        <td><input type="password" class="text_medium" name="password" size="20" maxlength="32">
                            <input type="hidden" name="vaction" value="login">
                            <input type="hidden" name="formtimestamp" value="<?php echo time(); ?>">
							<input type="hidden" name="idcat" value="<?php echo intval($idcat); ?>">
                            </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="right">
                            <input type="image" title="Login" alt="Login" src="../contenido/images/but_ok.gif">
                        </td>
                    </tr>
                </table>
            </form>
        </td>
    </tr>
</table>

<script type="text/javascript">
    if (document.login.username.value == '') {
        document.login.username.focus();

    } else {
        document.login.password.focus();

    }
</script>

</body>
</html>
