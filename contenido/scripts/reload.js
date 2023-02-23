/**
 * CONTENIDO JavaScript reload module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */

var left_bottom = Con.getFrame('left_bottom');
if (left_bottom.get_registered_parameters) {
    left_bottom.location.href = left_bottom.location.href + left_bottom.get_registered_parameters();
} else {
    left_bottom.location.href = left_bottom.location.href;
}