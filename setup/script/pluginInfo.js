/*****************************************
*
* $Id$
*
* File      : $RCSfile: pluginInfo.js,v $
* Project   : CONTENIDO
* Descr     : File contains functions for displaying plugin information layer, hiding and
              crorrecting its position, when browser window is resized
*
* Author    : $Author: timo.trautmann$
* Modified  : $Date: 2008/04/03 13:11:21 $
*
* © four for business AG, www.4fb.de
******************************************/

/**
 * Function returns offset left, top, width and heigth of a given htnmlelement as array
 *
 * @param object oElement - Object which should be analyzed
 * @return array - containing dimension information
 */
var getElementPostion = function (oElement) {
    var iHeigth = oElement.offsetHeight;
    var iWidth = oElement.offsetWidth;
    var iTop = 0, iLeft = 0;
    while (oElement) {
        iTop += oElement.offsetTop  || 0;
        iLeft += oElement.offsetLeft || 0;
        oElement = oElement.offsetParent;
    };
    return [iLeft, iTop, iHeigth, iWidth];
}

/**
 * Function set layer position absolutely. Basis for position is element plugin_layer, which contains
 * all avariable plugins
 *
 */
function setLayerPostion () {
    var oPluginLayer = document.getElementById('plugin_layer');
    //only correct position, if layer is currently displayed
    if (oPluginLayer.style.display = 'block') {
        var aPluginListPos = getElementPostion(document.getElementById('plugin_list'));

        oPluginLayer.style.top=aPluginListPos[1];
        oPluginLayer.style.left=aPluginListPos[0]+200;
    }
}

/**
 * Function shows plugin information layer and fills it with given information
 *
 * @param sHeader string - Name of the plugin
 * @param sDescription string - Description of the plugin
 */
function showPluginInfo(sHeader, sDescription) {
    setLayerPostion();

    //get plugin layer
    var oPluginLayer = document.getElementById('plugin_layer');

    //set layer data and display it
    document.getElementById('plugin_header').innerHTML=sHeader;
    document.getElementById('plugin_description').innerHTML=sDescription;
    oPluginLayer.style.display = 'block';
}

/**
 * Function hides plugin information layer
 *
 */
function hidePluginInfo() {
    document.getElementById('plugin_layer').style.display = 'none';
}

//update layer position when browser window is resized
window.onresize = setLayerPostion;