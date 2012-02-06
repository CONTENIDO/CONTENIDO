?><?php
/**
* $RCSfile$
*
* Description: Article Include Input
*
* @version 1.0.0
* @author Willi Man
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2003-12-18
* modified 2004-01-02
* modified 2005-08-05 Andreas Lindner
* }}
*
* $Id$
*/

#Get current settings
$name         = "CMS_VAR[1]";
$cms_idcat    = "CMS_VALUE[1]";
$cms_idcatart = "CMS_VALUE[2]";

#Cat selector
echo buildCategorySelect($name, $cms_idcat);

$bDebug = false;
if ($bDebug)
    echo "<pre>cat $cms_idcat catart $cms_idcatart client $client lang $lang <br>webpath ".$cfgClient[$client]['path']['htmlpath']."</pre>";

echo '<table cellpadding="0" cellspacing="0" border="0">';

#Article selector
echo '
      <tr><td class="text_medium" style="padding:5px">'.mi18n("Artikel wählen").': </td></tr>
      <tr><td class="text_medium" style="padding:5px">';
echo '<select name="CMS_VAR[2]" style="width:240px">';
echo '<option value="" selected>'.i18n("Please choose").'</option>';

if ($cms_idcat != "0" && strlen($cms_idcat) > 0) {
    $sql = "SELECT
                        a.title AS title,
                        b.idcatart AS idcatart
                       FROM
                        ".$cfg["tab"]["art_lang"]." AS a,
                        ".$cfg["tab"]["cat_art"]." AS b
                        WHERE
                        b.idcat = '".$cms_idcat."' AND
                        a.idart = b.idart AND
                        a.idlang = '".$lang."'";

    $db->query($sql);

    while ($db->next_record()) {

        $catartid = $db->f('idcatart');
        $title = $db->f('title');

        if ($cms_idcatart != $catartid) {
            echo '<option value="'.$catartid.'">&nbsp;'.$title.'</option>';
        } else {
            echo '<option selected="selected" value="'.$catartid.'">&nbsp;'.$title.'</option>';
        }

    }
}

echo '</select>&nbsp;<input type="image" src="images/submit.gif">';
echo '</td></tr></table>';
?><?php