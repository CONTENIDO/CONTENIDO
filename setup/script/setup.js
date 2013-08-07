/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO setup script
 *
 * @package    CONTENIDO Setup
 * @version    0.0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */


function applyImage(obj, img) {
    obj.src = img;
}

function clickHandler(obj) {
    obj.clicked = !obj.clicked;

    if (obj.clicked) {
        if (obj.mouseIn) {
            applyImage(obj, obj.clickimgover);
        } else {
            applyImage(obj, obj.clickimgnormal);
        }
    } else {
        if (obj.mouseIn) {
            applyImage(obj, obj.imgover);
        } else {
            applyImage(obj, obj.imgnormal);
        }
    }
}

function mouseoverHandler(obj) {
    obj.mouseIn = true;

    if (obj.clicked) {
        applyImage(obj, obj.clickimgover);
    } else {
        applyImage(obj, obj.imgover);
    }
}

function mouseoutHandler(obj) {
    obj.mouseIn = false;

    if (obj.clicked) {
        applyImage(obj, obj.clickimgnormal);
    } else {
        applyImage(obj, obj.imgnormal);
    }
}

function showHideMessage(obj, div) {
    if (!obj.clicked)     {
        div.className = 'entry_open';
    } else {
        div.className = 'entry_closed';
    }
}
