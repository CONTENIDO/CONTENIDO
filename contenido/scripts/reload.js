/**
 * CONTENIDO JavaScript reload module
 *
 * @version    SVN Revision $Rev$
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */

var left_bottom = Con.getFrame('left_bottom');
if (left_bottom.get_registered_parameters) {
    left_bottom.location.href = left_bottom.location.href + left_bottom.get_registered_parameters();
} else {
    left_bottom.location.href = left_bottom.location.href;
}