/**
 * CONTENIDO include.upl_edit.js JavaScript module.
 *
 * @version    SVN Revision $Rev$
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
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
