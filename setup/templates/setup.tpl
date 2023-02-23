<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/loose.dtd">
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
<!--
i18n("Can't write %s")
i18n("Setup or CONTENIDO can't write to the file %s. Please change the file permissions to correct this problem.")
i18n("Your Server runs Windows. Due to that, Setup can't recommend any file permissions.")
i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file %s, especially in environments where ACL (Access Control Lists) are used.")
i18n("Your web server and the owner of your files are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the file mask to %s or set the owner to allow writing the file.")
i18n("Your web server's group and the group of your files are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the file mask to %s or set the group to allow writing the file.")
i18n("Your web server is not equal to the file owner, and is not in the webserver's group. It would be highly insecure to allow world write access to the files. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the file mask to %s or set the others to allow writing the file.")
i18n("Your Server runs Windows. Due to that, Setup can't recommend any directory permissions.")
i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file or directory %s, especially in environments where ACL (Access Control Lists) are used.")
i18n("Your web server and the owner of your directory are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the directory mask to %s or set the owner to allow writing the directory.")
i18n("Your web server's group and the group of your directory are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the directory mask to %s or set the group to allow writing the directory.")
i18n("Your web server is not equal to the directory owner, and is not in the webserver's group. It would be highly insecure to allow world write access to the directory. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the directory mask to %s or set the others to allow writing the directory.")
-->