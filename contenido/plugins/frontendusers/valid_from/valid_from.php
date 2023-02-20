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

/**
 * @return string
 */
function frontendusers_valid_from_getTitle() {
    return i18n("Valid from");
}

/**
 * @return string|void
 * @throws cInvalidArgumentException
 */
function frontendusers_valid_from_display() {
    global $feuser;

    $belang = cRegistry::getBackendLanguage();
    $cfg = cRegistry::getConfig();

    $langScripts = '';

    if (($langShort = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != 'en') {
        $langScripts = cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/timepicker-' . $langShort . '.js')) . "\n"
            . cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/datepicker-' . $langShort . '.js'));
    }

    $calenderPicPath = cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif';

    $template = '%s';

    $currentValue = $feuser->get('valid_from');
    if ($currentValue == '') {
        $currentValue = '0000-00-00';
    }
    $currentValue = str_replace('00:00:00', '', $currentValue);

    $sValidFrom = '
        <link rel="stylesheet" type="text/css" href="' . cAsset::backend('styles/jquery/plugins/timepicker.css') . '">
{_JS_HEAD_CONTENIDO_}
        ' . cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/timepicker.js'));
    $sValidFrom .= $langScripts;

    $sValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="' . $currentValue . '">';
    $sValidFrom .= '<script type="text/javascript">
(function(Con, $) {
    $(function() {
        $("#valid_from").datetimepicker({
            buttonImage:"' . $calenderPicPath . '",
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

/**
 * @return array
 */
function frontendusers_valid_from_wantedVariables() {
    return (['valid_from']);
}

/**
 * @param $variables
 */
function frontendusers_valid_from_store($variables) {
    global $feuser;

    $feuser->set('valid_from', $variables['valid_from'], false);
}
