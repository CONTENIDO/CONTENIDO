?><?php
/**
 * Description: XING input
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
        <td><?php echo mi18n("URL_PROFILE");?></td>
        <td><input type="text" name="CMS_VAR[0]" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td>
            <?php echo mi18n("LOOK");?>
        </td>
        <td>
            <input type="radio" name="CMS_VAR[1]" value="small" <?php  $value = "CMS_VALUE[1]"; if ($value == "small" ) echo 'checked="checked"'; ?>>
            <img src="http://www.xing.com/img/n/xing_icon_32x32.png" alt="profil bild" />
            <br/>
            <input type="radio" name="CMS_VAR[1]" value="big"  <?php $value =  "CMS_VALUE[1]"; if ($value =="big" || $value !="big" && $value!="small") echo 'checked="checked"'; ?>>
            <img src="http://www.xing.com/img/buttons/1_de_btn.gif" alt="profil bild" />
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("NAME");?></td>
        <td><input type="text" name="CMS_VAR[2]" value="<?php echo "CMS_VALUE[2]"; ?>"></td>
    </tr>
</table>

<?php
