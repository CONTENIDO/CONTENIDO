?>

<table>
    <tr>
        <td><?php echo mi18n("URL");?></td>
        <td><input type="text" name="CMS_VAR[0]" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td>
            <?php echo mi18n("Aussehen");?>
        </td>
        <td>
            <input type="radio" name="CMS_VAR[1]" value="standard" <?php  $value = "CMS_VALUE[1]"; if ($value == "standard" || $value != "small" && $value != "medium" && $value != "tall" ) echo 'checked="checked"'; ?>">
            <?php echo mi18n("normal")." (24px)";?>
            <br/>
            <input type="radio" name="CMS_VAR[1]" value="small"  <?php $value =  "CMS_VALUE[1]"; if ($value =="small") echo 'checked="checked"'; ?>">
            <?php echo mi18n("klein")." (15px)";?>
            <br/>
            <input type="radio" name="CMS_VAR[1]" value="medium"  <?php $value =  "CMS_VALUE[1]"; if ($value == "medium") echo 'checked="checked"'; ?>">
            <?php echo mi18n("mittel")." (20px)";?>
            <br/>
            <input type="radio" name="CMS_VAR[1]" value="tall"  <?php $value =  "CMS_VALUE[1]"; if ($value =="tall") echo 'checked="checked"'; ?>">
            <?php echo mi18n("gro&szlig;")." (60px)";?>
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("Z&auml;hler anzeigen");?></td>
        <td>
            <input type="checkbox" name="CMS_VAR[3]"  value="1" <?php $value="CMS_VALUE[3]"; if ($value) echo 'checked="checked"'; ?>">
        </td>
    </tr>
</table>

<?php
