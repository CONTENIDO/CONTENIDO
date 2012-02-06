?><?php
/**
 * $RCSfile$
 *
 * Description: Contact Form Input
 *
 * @version 1.0.2
 * @author Andreas Lindner
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-08-12
 *   modified 2010-06-09 Ingo van Peeren
 *   modified 2011-11-09 Murat Purc, added configuration for SMTP port
 * }}
 *
 * $Id$
 */


// generate unique id, it's used for element id/for attributes in module
$mId = uniqid('m_');

?>

<table border="0">
    <tr>
        <td><?php echo mi18n("Absender EMail");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[0]";?>" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Absender Name");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[2]";?>" value="<?php echo "CMS_VALUE[2]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Empfänger EMail");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[1]";?>" value="<?php echo "CMS_VALUE[1]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("Betreff");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[3]";?>" value="<?php echo "CMS_VALUE[3]"; ?>"></td>
    </tr>
    <?php
    $c1 = '';
    $c2 = '';
    $c3 = '';
    $c4 = '';
    switch (strtolower("CMS_VALUE[4]")) {
        case "smtp":
            $c1 = ' checked';
            break;
        case "mail":
            $c2 = ' checked';
            break;
        case "sendmail":
            $c3 = ' checked';
            break;
        case "qmail":
            $c4 = ' checked';
            break;
        default:
            $c3 = ' checked';
    }
    ?>
    <tr>
        <td valign="top"><?php echo mi18n("Mailer");?></td>
        <td>
            <input type="radio" name="<?php echo "CMS_VAR[4]";?>" id="<?php echo $mId ?>_mailer_mail" value="mail"<?php echo $c2;?>>
            <label for="<?php echo $mId ?>_mailer_mail"><?php echo mi18n("mail");?></label><br />

            <input type="radio" name="<?php echo "CMS_VAR[4]";?>" id="<?php echo $mId ?>_mailer_qmail" value="qmail"<?php echo $c4;?>>
            <label for="<?php echo $mId ?>_mailer_qmail"><?php echo mi18n("qmail");?></label><br />

            <input type="radio" name="<?php echo "CMS_VAR[4]";?>" id="<?php echo $mId ?>_mailer_sendmail" value="sendmail"<?php echo $c3;?>>
            <label for="<?php echo $mId ?>_mailer_sendmail"><?php echo mi18n("sendmail");?></label><br />

            <input type="radio" name="<?php echo "CMS_VAR[4]";?>" id="<?php echo $mId ?>_mailer_smtp" value="smtp"<?php echo $c1;?>>
            <label for="<?php echo $mId ?>_mailer_smtp"><?php echo mi18n("smtp");?></label><br />
        </td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP Host");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[5]";?>" value="<?php echo "CMS_VALUE[5]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP User");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[6]";?>" value="<?php echo "CMS_VALUE[6]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP Passwort");?></td>
        <td><input type="text" name="<?php echo "CMS_VAR[7]";?>" value="<?php echo "CMS_VALUE[7]"; ?>"></td>
    </tr>
    <tr>
        <td><?php echo mi18n("SMTP Port");?></td>
        <td>
            <input type="text" name="<?php echo "CMS_VAR[8]";?>" value="<?php echo "CMS_VALUE[8]"; ?>"><br />
            <small><?php echo mi18n("(Standard Port is 25)");?></small>
        </td>
    </tr>
</table>
<?php