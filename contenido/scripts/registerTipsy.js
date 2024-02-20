/**
 * CONTENIDO JavaScript registerTips module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {
    $(function() {
        $('.tooltip').tipsy({
            gravity : $.fn.tipsy.autoWE,
            html : true
        });
        $('.tooltip-north').tipsy({
            gravity : 'ns',
            html : true
        });
    });
})(Con, Con.$);

