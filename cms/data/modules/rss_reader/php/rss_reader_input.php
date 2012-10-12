?>

<table>
    <tr>
        <td><?php echo mi18n("URL"); ?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[0]"; ?>" value="<?php echo "CMS_VALUE[0]"; ?>" style="width:320px"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("RSS_TEMPLATE_SELECT"); ?>:</td>
        <td>
            <select name="<?php echo  "CMS_VAR[1]"; ?>" size="1" style="width:320px">
                <option value=""><?php echo mi18n("NOTHING_SELECTED"); ?></option>
                <?php
                $strPath_fs = cRegistry::getFrontendPath() . 'templates/';
                $handle = opendir($strPath_fs);
                while ($entryName = readdir($handle)) {
                    if (is_file($strPath_fs.$entryName)) {
                        if ("CMS_VALUE[1]" == $entryName) {
                            echo '<option selected value="'.$entryName.'">'.$entryName.'</option>';
                        } else {
                            echo '<option value="'.$entryName.'">'.$entryName.'</option>';
                        }
                    }
                }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("COUNT_ITEM"); ?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[2]"; ?>" value="<?php echo "CMS_VALUE[2]"; ?>"></td>
    </tr>
</table>

<?php