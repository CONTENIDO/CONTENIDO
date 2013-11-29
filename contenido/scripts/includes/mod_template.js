/**
 * CONTENIDO mod_template.js JavaScript module.
 *
 * @version    SVN Revision $Rev: 5937 $
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
    // Changes the "delete template" link to always match the currently selected template
    // in the dropdown menu instead of the currently loaded one.
    $(function() {
        $(".fileChooser").change(function() {
            var link = document.getElementById("deleteLink").href;
            var newLink = link.substr(0, link.lastIndexOf("=") + 1) + $(".fileChooser option:selected").val();
            document.getElementById("deleteLink").href = newLink;
        });
    });
})(Con, Con.$);
