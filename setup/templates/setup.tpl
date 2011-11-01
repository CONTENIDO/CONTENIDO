<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>{TITLE}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link href="style/setup.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="script/setup.js"></script>
</head>
<body>

<div id="setupPageWrap">
    <div id="setupPage">
        <form name="setupform" method="post" action="index.php">
            <input type="hidden" name="step" value="">
            <div id="setupBox">
                <div id="setupHead">
                    <img src="images/logo.gif" alt="CONTENIDO Logo">
                </div>
                <div id="setupHeadlinePath">
                    <div class="column-1">{HEADER}</div><div class="column-2">{STEPS}</div>
                </div>
                <div id="setupBody">
                    {CONTENT}
                </div>
            </div>
            <div id="setupFootnote">
                &copy; <b>four for business AG</b>
            </div>
        </form>
    </div>
</div>

</body>
</html>