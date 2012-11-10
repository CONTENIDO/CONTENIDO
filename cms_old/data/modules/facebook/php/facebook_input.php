?>

<table>
    <tr>
        <td><?php echo mi18n("URL");?>*</td>
        <td><input type="text" name="CMS_VAR[0]" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td>
            <?php echo mi18n("PLUGIN");?>
        </td>
        <td>
            <input type="radio" name="CMS_VAR[1]" value="like_button" <?php $value = "CMS_VALUE[1]"; if ($value == "like_button" ) echo 'checked="checked"'; ?>">
            <?php echo mi18n("LIKE_BUTTON");?>
            <br/>
            <input type="radio" name="CMS_VAR[1]" value="like_box" <?php $value = "CMS_VALUE[1]"; if ($value =="like_box" || $value !="like_button" && $value!="like_box") echo 'checked="checked"'; ?>">
            <?php echo mi18n("LIKE_BOX");?>
        </td>
    </tr>
    <tr>
        <td>
            <?php echo mi18n("LAYOUT");?>
        </td>
        <td>
            <input type="radio" name="CMS_VAR[2]" value="standard" <?php $value = "CMS_VALUE[2]"; if ($value == "standard" || $value !="button_count" && $value !="box_count" ) echo 'checked="checked"'; ?>">
            <?php echo mi18n("STANDARD");?>
            <br/>
            <input type="radio" name="CMS_VAR[2]" value="button_count" <?php $value = "CMS_VALUE[2]"; if ($value =="button_count") echo 'checked="checked"'; ?>">
            <?php echo mi18n("BUTTON_COUNT");?>
            <br/>
            <input type="radio" name="CMS_VAR[2]" value="box_count" <?php $value = "CMS_VALUE[2]"; if ($value == "box_count") echo 'checked="checked"'; ?>">
            <?php echo mi18n("BOX_COUNT");?>
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("SHOW_FACES");?></td>
        <td>
            <input type="checkbox" name="CMS_VAR[3]" value="true" <?php $value = "CMS_VALUE[3]"; if ($value) echo 'checked="checked"'; ?>">
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("WIDTH");?></td>
        <td><input type="text" name="CMS_VAR[4]" value="<?php echo "CMS_VALUE[4]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("HEIGHT");?></td>
        <td><input type="text" name="CMS_VAR[6]" value="<?php echo "CMS_VALUE[6]"; ?>"></td>
    </tr>
 </table>

<?php
