/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO setup script
 *
 * Requirements:
 * -/-
 *
 * @package    CONTENIDO setup
 * @version    0.0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.9.0
 *
 * {@internal
 *   created 2011-01-11  Murat Purc, moved JS code from template to this new file
 *   $Id
 * }}
 *
 */


var isMSIE = (navigator.appName == "Microsoft Internet Explorer");

if (navigator.userAgent.indexOf("Opera") != -1)
{
    isMSIE = false;
}

function IEAlphaInit(obj)
{
    if (isMSIE && !obj.IEswapped) { obj.IEswapped = true; obj.src = 'images/spacer.gif'; }
}

function IEAlphaApply(obj, img)
{
    if (isMSIE) { obj.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+img+"');" } else { obj.src=img; }
}

function clickHandler(obj)
{
    if (obj.clicked) { obj.clicked = false; } else { obj.clicked = true }

    if (obj.clicked)
    {
        if (obj.mouseIn)
        {
            IEAlphaApply(obj, obj.clickimgover);
        } else {
            IEAlphaApply(obj, obj.clickimgnormal);
        }
    } else {
        if (obj.mouseIn)
        {
            IEAlphaApply(obj, obj.imgover);
        } else {
            IEAlphaApply(obj, obj.imgnormal);
        }
    }
}

function mouseoverHandler(obj)
{
    obj.mouseIn = true;

    if (obj.clicked)
    {
        IEAlphaApply(obj, obj.clickimgover);
    } else {
        IEAlphaApply(obj, obj.imgover);
    }
}

function mouseoutHandler(obj)
{
    obj.mouseIn = false;

    if (obj.clicked)
    {
        IEAlphaApply(obj, obj.clickimgnormal);
    } else {
        IEAlphaApply(obj, obj.imgnormal);
    }
}

function showHideMessage(obj, div)
{
    if (!obj.clicked)
    {
        div.className = 'entry_open';
    } else {
        div.className = 'entry_closed';
    }
}
