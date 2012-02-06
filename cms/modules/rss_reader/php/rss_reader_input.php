?><?php
/**
* $RCSfile$
*
* Description: Display an RSS Feed. Module "Input".
*
* @version 1.0.0
* @author Timo Hummel, Andreas Lindner
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2005-09-30
* }}
*
* $Id$
*/

echo '<table>';
echo '<tr><td>'.mi18n("URL").'</td><td><input type="text" name="'."CMS_VAR[0]".'" value="'."CMS_VALUE[0]".'" style="width: 320px"></td></tr>';
echo '<tr><td>';
echo mi18n("RSS-Template auswählen").':</td><td><select name="CMS_VAR[1]" size="1" style="width: 320px">
<option value="">Nichts ausgewählt</option>';
$strPath_fs = $cfgClient[$client]["path"]["frontend"].'templates/';
$handle = opendir($strPath_fs);
while ($entryName = readdir($handle)) {
    if (is_file($strPath_fs.$entryName)) {
        if ("CMS_VALUE[1]"==$entryName) {
            echo '<option selected value="'.$entryName.'">'.$entryName.'</option>';
        } else {
            echo '<option value="'.$entryName.'">'.$entryName.'</option>';
        }
    }
}
echo '</select></td></tr>';

echo '<tr><td>'.mi18n("Anzahl Einträge").'</td><td><input type="text" name="'."CMS_VAR[2]".'" value="'."CMS_VALUE[2]".'"</td></tr>';

echo '</table>';

?><?php