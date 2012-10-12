?><?php
/**
 * Description: Twitter input
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

?>

<table>
    <tr>
        <td><?php echo mi18n("TWITTERNAME");?></td>
        <td><input type="text" name="CMS_VAR[0]" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td>
            <?php echo mi18n("LOOK");?>
        </td>
        <td>
            <input type="radio" name="CMS_VAR[1]" value="small" <?php $value = "CMS_VALUE[1]"; if ($value == "small" ) echo 'checked="checked"'; ?>>
            <?php echo mi18n("SMALL");?>
            <br/>
            <input type="radio" name="CMS_VAR[1]" value="big" <?php $value = "CMS_VALUE[1]"; if ($value == "big" || $value != "big" && $value != "small") echo 'checked="checked"'; ?>>
            <?php echo mi18n("BIG");?>
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("SHOW_TWEETS ");?></td>
        <td>
            <input type="checkbox" name="CMS_VAR[2]" value="1" <?php $value = "CMS_VALUE[2]"; if ($value) echo 'checked="checked"'; ?>>
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("COUNT_TWEETS");?></td>
        <td><input type="text" name="CMS_VAR[3]" value="<?php echo "CMS_VALUE[3]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SHOW_FOLLOW_BUTTON");?></td>
    <td>
        <input type="checkbox" name="CMS_VAR[4]" value="1" <?php $value = "CMS_VALUE[4]"; if ($value) echo 'checked="checked"'; ?>>
    </td>
    </tr>
    <tr>
        <td><?php echo mi18n("SHOW_TWEET_BUTTON");?></td>
        <td>
            <input type="checkbox" name="CMS_VAR[5]" value="1" <?php $value = "CMS_VALUE[5]"; if ($value) echo 'checked="checked"'; ?>>
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("DEFAULT_TEXT");?></td>
        <td><input type="text" name="CMS_VAR[6]" value="<?php echo "CMS_VALUE[6]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("URL_TO_SHARE");?></td>
        <td><input type="text" name="CMS_VAR[7]" value="<?php echo "CMS_VALUE[7]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SHOW_COUNT");?></td>
        <td>
            <input type="checkbox" name="CMS_VAR[8]" value="1" <?php $value="CMS_VALUE[8]"; if ($value) echo 'checked="checked"'; ?>>
        </td>
    </tr>
</table>

<?php
