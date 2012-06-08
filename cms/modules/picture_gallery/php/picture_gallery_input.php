?><?php
/**
 * Description: Picture gallery input
 *
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-09-30
 *   $Id$
 * }}
 */

?>

<table>
    <tr>
        <td><?php echo mi18n("Breite");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[0]"; ?>" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("H&ouml;he");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[1]"; ?>" value="<?php echo "CMS_VALUE[1]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Spalten");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[2]"; ?>" value="<?php echo "CMS_VALUE[2]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Zeilen");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[3]"; ?>" value="<?php echo "CMS_VALUE[3]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Breite Detailansicht");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[4]"; ?>" value="<?php echo "CMS_VALUE[4]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Verzeichnis ausw&auml;hlen"); ?></td>
        <td>
            <select name="CMS_VAR[5]" size="1" style="width: 320px">
                <option value=""><?php echo mi18n("Nichts ausgew&auml;hlt"); ?></option>
                <?php
                $sql = "SELECT DISTINCT dirname FROM ".$cfg['tab']['upl']." ORDER BY dirname";
                $db->query($sql);
                while ($db->next_record()) {
                    if (stristr($db->f("dirname"), 'CVS/') === false) {
                        if ($db->f("dirname") == "CMS_VALUE[5]") {
                            echo '<option value="'.$db->f("dirname").'" selected="selected">'.$db->f("dirname").'</option>';
                        } else {
                            echo '<option value="'.$db->f("dirname").'">'.$db->f("dirname").'</option>';
                        }
                    }
                }
                ?>
            </select>
        </td>
    </tr>
</table>

<?php
