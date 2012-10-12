?><?php
/**
 * Description: Contact Form Input
 *
 * @version 1.0.2
 * @author Andreas Lindner
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-08-12
 *   $Id$
 * }}
 */

// generate unique id, it's used for element id/for attributes in module
$mId = uniqid('m_');

?>

<table>
    <tr>
        <td><?php echo mi18n("SENDER_EMAIL");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[0]";?>" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SENDER_NAME");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[2]";?>" value="<?php echo "CMS_VALUE[2]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("RECIPIENTS_EMAIL");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[1]";?>" value="<?php echo "CMS_VALUE[1]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SUBJECT");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[3]";?>" value="<?php echo "CMS_VALUE[3]"; ?>"></td>
    </tr>
    <?php
    $c1 = '';
    $c2 = '';
    switch (strtolower("CMS_VALUE[4]")) {
        case "smtp":
            $c1 = ' checked';
            break;
        case "mail":
            $c2 = ' checked';
            break;
        default:
            $c2 = ' checked';
    }
    ?>
    <tr>
        <td valign="top"><?php echo mi18n("MAILER");?></td>
        <td>
            <input type="radio" name="<?php echo "CMS_VAR[4]";?>" id="<?php echo $mId ?>_mailer_mail" value="mail"<?php echo $c2;?>>
            <label for="<?php echo $mId ?>_mailer_mail"><?php echo mi18n("MAIL");?></label><br />

            <input type="radio" name="<?php echo "CMS_VAR[4]";?>" id="<?php echo $mId ?>_mailer_smtp" value="smtp"<?php echo $c1;?>>
            <label for="<?php echo $mId ?>_mailer_smtp"><?php echo mi18n("SMTP");?></label><br />
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP_HOST");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[5]";?>" value="<?php echo "CMS_VALUE[5]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP_USER");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[6]";?>" value="<?php echo "CMS_VALUE[6]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP_PASSWORD");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[7]";?>" value="<?php echo "CMS_VALUE[7]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP_PORT");?></td>
        <td>
            <input type="text" name="<?php echo "CMS_VAR[8]";?>" value="<?php echo "CMS_VALUE[8]"; ?>"><br />
            <small><?php echo mi18n("(STANDARD_PORT_IS_25)");?></small>
        </td>
    </tr>
</table>

<?php