/**
 * CONTENIDO JavaScript folding row helper module
 *
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */

function fncSetPager(sId, sCurPage) {
    var oLeftTop = Con.getFrame('left_top');
    if (oLeftTop.document) {
        var oPager = oLeftTop.document.getElementById(sId);
        if (oPager) {
            oInsert = oPager.firstChild;
            oInsert.innerHTML = sNavigation;
            oLeftTop.newsletter_listoptionsform_curPage = sCurPage;
            oLeftTop.toggle_pager(sId);
            window.clearInterval(oTimer);
        }
    }
}
