/**
 * Project: CONTENIDO Content Management System
 *
 * Description: CONTENIDO setup script
 *
 * @package    CONTENIDO Setup
 * @version    0.0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
    if (!obj.clicked) {
        div.className = 'entry_open';
    } else {
        div.className = 'entry_closed';
    }
}

var advancedSettings = false;

function toggleSettings() {
    var rows = document.getElementsByClassName("advancedSetting");
    var image = document.getElementsByClassName("advancedSettingsImage");
    image[0].src = advancedSettings ? "images/controls/arrow_closed.png"
        : "images/controls/arrow_open.png";
    for (var i = 0; i < rows.length; i++) {
        if (advancedSettings) {
            rows[i].style.visibility = "hidden";
        } else {
            rows[i].style.visibility = "visible";
        }
    }
    advancedSettings = !advancedSettings;
}

function comboBox(list, input) {
    input = document.getElementById(input);
    list = document.getElementById(list);
    var idx = list.selectedIndex;
    var content = list.options[idx].value;
    input.value = content;
}
