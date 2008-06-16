/******************************************
* File      :   general.js
* Project   :   Contenido
* Descr     :   Defines general required
*               javascript functions
*
* Author    :   Jan Lengowski
* Created   :   25.03.2003
* Modified  :   $Date$
*
* $Id$

* © four for business AG
******************************************/

/**
 * Javascript Multilink
 *
 *  Example:
 *  <code>
 *	conMultiLink (
 *	 	"frame",
 *		"link",
 * 		"frame",
 *		"link",
 *		 ...,
 *		"simpleFrame"
 *	)
 * </code>
 *
 * @param [arguments*] optional amount of arguments used pairwise for assigning URLs to frame names in Contenido.
 *                     The last argument is optional but must (!) be "simpleFrame" if used to specify that the complete frame structure is not available.
 * @return void
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Marco Jahn <Marco.Jahn@4fb.de>
 * @author Frederic Schneider <Frederic.Schneider@4fb.de>
 * @copryright four for business AG <www.4fb.de>
 */
function conMultiLink()
{
    // get last argument
    var tmp = arguments[arguments.length-1];
    // check by last argument if reduced frame structure is used
    var simpleFrame = (tmp == "simpleFrame") ? true : false ;
    // change for-loop counter if last parameter is used to identify simple frame multilinks
    var len = (simpleFrame) ? arguments.length - 1 : arguments.length;

	for (var i = 0; i < len; i += 2) {
		f = arguments[i];
		l = arguments[i + 1];

        if (f == "left_bottom" || f == "left_top") {
            parent.parent.frames["left"].frames[f].location.href = l;
        } else {
            if (simpleFrame) { // use simple frame
                parent.frames[f].location.href = l;
            } else { // use classic multilink structure
                parent.parent.frames["right"].frames[f].location.href = l;
            }
        }
	}
}

function handleErrors() {

    return true;
}