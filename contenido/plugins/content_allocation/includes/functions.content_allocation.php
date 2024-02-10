<?php

/**
 * This file contains various functions for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Builds and returns the HTML markup for content allocation item form.
 *
 * @param string $step Current content allocation step
 * @param string $nextStep Next content allocation step
 * @param string $action Backend action
 * @param int $frame Destination frame
 * @param string $sessId Session id
 * @param string $area Backend area
 * @param string $fieldName Content allocation item field name
 * @param int|string $fieldValue Content allocation item field value, integer or 'root'
 * @param string $name Name of content allocation item
 * @return string
 * @throws cException
 */
function piContentAllocationBuildContentAllocationForm(
    $step, $nextStep, $action, $frame, $sessId, $area, $fieldName, $fieldValue, $name
)
{
    $newRow = '';
    if ($step === 'createRoot') {
        $newRow = '<tr><td colspan="2" class="text_medium">' . i18n("Create new tree", 'content_allocation') . '</td></tr>';
    }

    if (!empty($name)) {
        $name = conHtmlentities($name);
    }

    $msg = i18n("Please enter a category name", 'content_allocation');

    return <<<HTML
    <table>
        <form id="content_allocation_form" name="create" action="main.php" method="POST">
        <input type="hidden" name="action" value="$action">
        <input type="hidden" name="frame" value="$frame">
        <input type="hidden" name="contenido" value="$sessId">
        <input type="hidden" name="area" value="$area">
        <input type="hidden" name="step" value="$nextStep">
        <input type="hidden" name="$fieldName" value="$fieldValue">
        $newRow
        <tr>
            <td class="no_border pdt0 pdb0 pdl10"><input id="itemname" class="text_medium" type="text" name="treeItemPost[name]" value="$name"></td>
            <td class="no_border pdt0 pdb0">
                <a class="con_img_button" href="main.php?action=$action&frame=$frame&area=$area&contenido=$sessId"><img src="images/but_cancel.gif" alt=""></a>
                <input class="con_img_button" type="image" src="images/but_ok.gif" alt="">
            </td>
        </tr>
        </form>
    </table>
    <script type="text/javascript">
    (function(Con, $) {
        $(function() {
            var \$form = $("#content_allocation_form");
            \$form.on("submit", function() {
                var \$element = $("#itemname");
                if (\$element.val().trim() === "") {
                    alert("$msg");
                    \$element.focus();
                    return false;
                }
                return true;
            });
        });
    })(Con, Con.\$);
    </script>
HTML;
}