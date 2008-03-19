/******************************************
* File      :   general.js
* Project   :   Contenido
* Descr     :   Defines general required
*               javascript functions
*
* Author    :   Jan Lengowski
* Created   :   25.03.2003
* Modified  :   25.03.2003
*
* © four for business AG
******************************************/

/**
 * Javascript Multilink
 *
 * @param name-value-pairs framename src
 * @return void
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copryright four for business AG <www.4fb.de>
 */
function conMultiLink() 
{
	for (var i = 0, len = arguments.length; i < len; i += 2) {
		f = arguments[i];
		l = arguments[i + 1];

    if (f == "left_bottom" || f == "left_top")
    {
      parent.parent.frames["left"].frames[f].location.href = l;
    } 
    else 
    {
      parent.parent.frames["right"].frames[f].location.href = l;
    }
	}
}
/**
 *
 *
 *
 *
 */
 
//window.onerror = handleErrors;

function handleErrors() {
  
    return true;
}
