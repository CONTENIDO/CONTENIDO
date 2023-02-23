/**
 * CONTENIDO include.upl_edit.js JavaScript module.
 *
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {
    $(function() {
        $(".ZipExtract").hide();
        $("#13").hide();
    });

    function show() {
        if ($("#m8").is(":checked")) {
            $(".ZipExtract").show();
            $("#13").show();
        } else {
            $(".ZipExtract").hide();
            $("#13").hide();
        }
    }

    window.show = show;
})(Con, Con.$);
