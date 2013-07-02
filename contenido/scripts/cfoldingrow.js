/*****************************************
* File      :   $RCSfile: class.frontend.groups.php,v $
* Project   :   CONTENIDO
* Descr     :   cGuiFoldingRow JavaScript helpers
* Modified  :   $Date: 2004/03/16 13:41:45 $
*
* � four for business AG, www.4fb.de
*
* $Id$
******************************************/

/**
 * Expands or collapses a cGuiFoldingRow.
 * The new state is registered for the given user.
 * 
 * @param image
 * @param row
 * @param hidden
 * @param uuid
 */
function cGuiFoldingRow_expandCollapse (image, row, hidden, uuid) {
    if (document.getElementById(image).getAttribute("data") == "collapsed") {
        document.getElementById(row).style.display = '';
        document.getElementById(image).setAttribute("src", "images/widgets/foldingrow/expanded.gif");
        document.getElementById(image).setAttribute("data", "expanded");
        document.getElementById(hidden).setAttribute("value", "expanded");
        register_parameter("u_register[expandstate][" + uuid + "]", "true");
    } else {
        document.getElementById(row).style.display = 'none';
        document.getElementById(image).setAttribute("src", "images/widgets/foldingrow/collapsed.gif");
        document.getElementById(image).setAttribute("data", "collapsed");
        document.getElementById(hidden).setAttribute("value", "collapsed");
        register_parameter("u_register[expandstate][" + uuid + "]", "false");
    }
}