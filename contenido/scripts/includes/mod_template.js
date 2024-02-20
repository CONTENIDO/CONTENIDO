/**
 * CONTENIDO mod_template.js JavaScript module.
 *
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {
    // Changes the "delete template" link to always match the currently selected template
    // in the dropdown menu instead of the currently loaded one.
    $(function() {
        $(".fileChooser").change(function() {
            var link = document.getElementById("deleteLink").href;
            var newLink = link.substr(0, link.lastIndexOf("=") + 1) + encodeURIComponent($(".fileChooser option:selected").val());
            document.getElementById("deleteLink").href = newLink;
        });
    });
})(Con, Con.$);
