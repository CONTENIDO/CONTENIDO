<?php
/**
 * This file contains the valid_to extension of the frontend user plugin.
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

function frontendusers_valid_to_getTitle ()
{
    return i18n("Valid to");
}

function frontendusers_valid_to_display ()
{
    global $feuser,$db,$belang,$cfg;

    $langscripts = '';


    $path_to_calender_pic =  cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif';

    $template  = '%s';

    $currentValue = $feuser->get("valid_to");

    if ($currentValue == '') {
        $currentValue = '0000-00-00';
    }
    $currentValue = str_replace('00:00:00', '', $currentValue);

    // js-includes are defined in valid_from
    $sValidFrom = '<input type="text" id="valid_to" name="valid_to" value="'.$currentValue.'">';
    $sValidFrom .= '<script type="text/javascript">
(function(Con, $) {
        $(function() {
    $("#valid_to").datetimepicker({
        buttonImage: "'. $path_to_calender_pic .'",
        buttonImageOnly: true,
        showOn: "both",
        dateFormat: "yy-mm-dd",
        onClose: function(dateText, inst) {
            var startDateTextBox = $("#valid_from");
            if (startDateTextBox.val() != "") {
                var testStartDate = new Date(startDateTextBox.val());
                var testEndDate = new Date(dateText);
                if (testStartDate > testEndDate) {
                    startDateTextBox.val(dateText);
                }
            } else {
                startDateTextBox.val(dateText);
            }
        },
        onSelect: function(selectedDateTime) {
            var end = $(this).datetimepicker("getDate");
            $("#valid_from").datetimepicker("option", "maxDate", new Date(end.getTime()));
        }
    });
            });
})(Con, Con.$);
</script>';

    return sprintf($template,$sValidFrom);
}

function frontendusers_valid_to_wantedVariables()
{
    return (array("valid_to"));
}

function frontendusers_valid_to_store($variables)
{
    global $feuser;

    $feuser->set("valid_to", $variables["valid_to"], false);
}
?>
