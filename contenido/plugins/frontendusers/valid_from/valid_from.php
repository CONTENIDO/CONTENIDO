<?php
/**
 * This file contains the valid_from extension of the frontend user plugin.
 *
 * @package    Plugin
 * @subpackage FrontendUsers
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function frontendusers_valid_from_getTitle() {
    return i18n("Valid from");
}

function frontendusers_valid_from_display() {
    global $feuser, $db, $belang, $cfg;

    $langscripts = '';

    if (($lang_short = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != "en") {
        $langscripts = '<script type="text/javascript" src="scripts/jquery/plugins/timepicker-' . $lang_short . '.js"></script>
        <script type="text/javascript" src="scripts/jquery/plugins/datepicker-' . $lang_short . '.js"></script>';
    }

    $path_to_calender_pic = cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif';

    $template = '%s';

    $currentValue = $feuser->get("valid_from");

    if ($currentValue == '') {
        $currentValue = '0000-00-00';
    }
    $currentValue = str_replace('00:00:00', '', $currentValue);

    $sValidFrom = '
        <link rel="stylesheet" type="text/css" href="styles/jquery/plugins/timepicker.css">
{_JS_HEAD_CONTENIDO_}
        <script type="text/javascript" src="scripts/jquery/plugins/timepicker.js"></script>';
    $sValidFrom .= $langscripts;

    $sValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="' . $currentValue . '">';
    $sValidFrom .= '<script type="text/javascript">
(function(Con, $) {
    $(function() {
        $("#valid_from").datetimepicker({
            buttonImage:"' . $path_to_calender_pic . '",
            buttonImageOnly: true,
            showOn: "both",
            dateFormat: "yy-mm-dd",
            onClose: function(dateText, inst) {
                var endDateTextBox = $("#valid_to");
                if (endDateTextBox.val() != "") {
                    var testStartDate = new Date(dateText);
                    var testEndDate = new Date(endDateTextBox.val());
                    if (testStartDate > testEndDate) {
                        endDateTextBox.val(dateText);
                    }
                } else {
                    endDateTextBox.val(dateText);
                }
            },
            onSelect: function(selectedDateTime) {
                var start = $(this).datetimepicker("getDate");
                $("#valid_to").datetimepicker("option", "minDate", new Date(start.getTime()));
            }
        });
    });
})(Con, Con.$);
</script>';

    $template = sprintf($template, $sValidFrom);
    $oTemplate = new cTemplate();
    return $oTemplate->generate($template, 1);
}

function frontendusers_valid_from_wantedVariables() {
    return (array("valid_from"));
}

function frontendusers_valid_from_store($variables) {
    global $feuser;

    $feuser->set("valid_from", $variables["valid_from"], false);
}

?>
